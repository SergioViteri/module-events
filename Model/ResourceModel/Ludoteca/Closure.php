<?php

namespace Zaca\Events\Model\ResourceModel\Ludoteca;

use Magento\Framework\Model\ResourceModel\Db\AbstractDb;

class Closure extends AbstractDb
{
    protected function _construct()
    {
        $this->_init('zaca_events_ludoteca_closure', 'closure_id');
    }

    /**
     * Confirmed bookings (not cancelled) that already fall inside a closure
     * period. Closing never cancels them; the admin is only warned.
     */
    public function countConfirmedBookings(?int $locationId, string $startDate, string $endDate): int
    {
        $connection = $this->getConnection();
        $select = $connection->select()
            ->from($this->getTable('zaca_events_table_booking'), ['total' => new \Zend_Db_Expr('COUNT(*)')])
            ->where('status = ?', 'confirmed')
            ->where('booking_date >= ?', $startDate)
            ->where('booking_date <= ?', $endDate);
        if ($locationId !== null) {
            $select->where('location_id = ?', $locationId);
        }
        return (int) $connection->fetchOne($select);
    }
}
