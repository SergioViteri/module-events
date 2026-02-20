<?php
/**
 * Zacatrus Events Registration Resource Model
 *
 * @category    Zacatrus
 * @package     Zacatrus_Events
 * @author      Zacatrus
 */

namespace Zaca\Events\Model\ResourceModel;

use Magento\Framework\Model\ResourceModel\Db\AbstractDb;

class Registration extends AbstractDb
{
    /**
     * @inheritdoc
     */
    protected function _construct()
    {
        $this->_init('zaca_events_registration', 'registration_id');
        // Note: table column is meet_id, not event_id
    }

    /**
     * Get sum of attendee_count for confirmed registrations of a meet
     *
     * @param int $meetId
     * @return int
     */
    public function getConfirmedAttendeeSum(int $meetId): int
    {
        $connection = $this->getConnection();
        $select = $connection->select()
            ->from($this->getMainTable(), [new \Magento\Framework\DB\Sql\Expression('COALESCE(SUM(attendee_count), 0)')])
            ->where('meet_id = ?', $meetId)
            ->where('status = ?', 'confirmed');
        $result = $connection->fetchOne($select);
        return (int) $result;
    }
}

