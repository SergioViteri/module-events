<?php
/**
 * Sends reminder emails for upcoming ludoteca bookings.
 *
 * Runs daily. Reads zaca_events/ludoteca/reminder_days_before (e.g. "1" or
 * "2,1"). For each N in that list, finds confirmed bookings where
 * booking_date == today + N days and sends a reminder if not already sent.
 *
 * Idempotency: zaca_events_ludoteca_reminder_sent has UNIQUE(booking_id,
 * reminder_days). The cron INSERTs first; on duplicate-key it skips and does
 * not send. So even if the cron runs twice on the same day, customers get a
 * single reminder per (booking, N).
 */

namespace Zaca\Events\Cron;

use Magento\Framework\App\ResourceConnection;
use Psr\Log\LoggerInterface;
use Zaca\Events\Helper\Data as Helper;
use Zaca\Events\Helper\LudotecaEmail;

class SendLudotecaReminders
{
    private ResourceConnection $resource;
    private Helper $helper;
    private LudotecaEmail $email;
    private LoggerInterface $logger;

    public function __construct(
        ResourceConnection $resource,
        Helper $helper,
        LudotecaEmail $email,
        LoggerInterface $logger
    ) {
        $this->resource = $resource;
        $this->helper = $helper;
        $this->email = $email;
        $this->logger = $logger;
    }

    public function execute(): int
    {
        $this->logger->info('[Ludoteca Reminders] Starting reminder cron job');

        $reminderDays = $this->helper->getReminderDaysBefore();
        if (empty($reminderDays)) {
            $this->logger->info('[Ludoteca Reminders] No reminder days configured, exiting.');
            return 0;
        }

        $today = new \DateTimeImmutable('today');
        $totalSent = 0;

        foreach ($reminderDays as $n) {
            $targetDate = $today->modify('+' . $n . ' days')->format('Y-m-d');
            $bookingIds = $this->fetchBookingsForDate($targetDate);
            foreach ($bookingIds as $bookingId) {
                if (!$this->markAsSent($bookingId, $n)) {
                    continue; // Already sent (UNIQUE constraint hit).
                }
                if ($this->email->sendBookingReminder($bookingId, $n)) {
                    $totalSent++;
                } else {
                    $this->unmarkAsSent($bookingId, $n);
                }
            }
        }

        $this->logger->info('[Ludoteca Reminders] Cron job completed. Total reminders sent: ' . $totalSent);
        return $totalSent;
    }

    /**
     * @return int[]
     */
    private function fetchBookingsForDate(string $ymd): array
    {
        $connection = $this->resource->getConnection();
        $rows = $connection->fetchCol(
            $connection->select()
                ->from(
                    $this->resource->getTableName('zaca_events_table_booking'),
                    ['booking_id']
                )
                ->where('status = ?', 'confirmed')
                ->where('booking_date = ?', $ymd)
        );
        return array_map('intval', $rows);
    }

    /**
     * Reserves a (booking, reminder_days) row before the email is sent.
     * Returns false if a row already existed (we should NOT send again).
     */
    private function markAsSent(int $bookingId, int $days): bool
    {
        $connection = $this->resource->getConnection();
        $table = $this->resource->getTableName('zaca_events_ludoteca_reminder_sent');
        try {
            $connection->insert($table, [
                'booking_id' => $bookingId,
                'reminder_days' => $days,
            ]);
            return true;
        } catch (\Magento\Framework\DB\Adapter\DuplicateException $e) {
            return false;
        } catch (\Zend_Db_Statement_Exception $e) {
            if ((int) $e->getCode() === 23000) {
                return false;
            }
            $this->logger->error('[Ludoteca Reminders] markAsSent DB error: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Removes the marker — used when the email send fails after we marked it.
     */
    private function unmarkAsSent(int $bookingId, int $days): void
    {
        $connection = $this->resource->getConnection();
        try {
            $connection->delete(
                $this->resource->getTableName('zaca_events_ludoteca_reminder_sent'),
                ['booking_id = ?' => $bookingId, 'reminder_days = ?' => $days]
            );
        } catch (\Throwable $e) {
            $this->logger->error('[Ludoteca Reminders] unmarkAsSent error: ' . $e->getMessage());
        }
    }
}
