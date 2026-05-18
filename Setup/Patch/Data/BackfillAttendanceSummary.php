<?php
/**
 * Backfill zaca_events_attendance_summary from existing registrations/attendance.
 * Idempotent: re-running consolidates counts back to the authoritative source.
 *
 * @category    Zacatrus
 * @package     Zaca_Events
 * @author      Zacatrus
 */

namespace Zaca\Events\Setup\Patch\Data;

use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\Framework\Setup\Patch\DataPatchInterface;

class BackfillAttendanceSummary implements DataPatchInterface
{
    private ModuleDataSetupInterface $moduleDataSetup;

    public function __construct(ModuleDataSetupInterface $moduleDataSetup)
    {
        $this->moduleDataSetup = $moduleDataSetup;
    }

    public function apply(): void
    {
        $connection = $this->moduleDataSetup->getConnection();
        $summary = $this->moduleDataSetup->getTable('zaca_events_attendance_summary');
        $registration = $this->moduleDataSetup->getTable('zaca_events_registration');
        $meet = $this->moduleDataSetup->getTable('zaca_events_meet');

        if (!$connection->isTableExists($summary)
            || !$connection->isTableExists($registration)
            || !$connection->isTableExists($meet)
        ) {
            return;
        }

        // Aggregate per (customer_id, theme_id) and upsert. VALUES(attendance_count) overwrites
        // any previous row so the patch is idempotent against drift.
        $sql = sprintf(
            'INSERT INTO %s (customer_id, theme_id, attendance_count) '
            . 'SELECT r.customer_id, m.theme_id, SUM(r.attendance_count) '
            . 'FROM %s r '
            . 'INNER JOIN %s m ON m.meet_id = r.meet_id '
            . 'WHERE r.attendance_count > 0 AND m.theme_id IS NOT NULL '
            . 'GROUP BY r.customer_id, m.theme_id '
            . 'ON DUPLICATE KEY UPDATE attendance_count = VALUES(attendance_count)',
            $connection->quoteIdentifier($summary),
            $connection->quoteIdentifier($registration),
            $connection->quoteIdentifier($meet)
        );
        $connection->query($sql);
    }

    public static function getDependencies(): array
    {
        return [];
    }

    public function getAliases(): array
    {
        return [];
    }
}
