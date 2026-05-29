<?php
/**
 * Seed default ludoteca time slots for every active location.
 *
 * Idempotent: skips locations that already have any time slot row.
 */

namespace Zaca\Events\Setup\Patch\Data;

use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\Framework\Setup\Patch\DataPatchInterface;

class SeedLudotecaTimeSlots implements DataPatchInterface
{
    private const DEFAULT_SLOTS = [
        ['10:30:00', '12:00:00'],
        ['12:00:00', '13:30:00'],
        ['17:00:00', '19:00:00'],
        ['19:00:00', '20:30:00'],
    ];

    private ModuleDataSetupInterface $moduleDataSetup;

    public function __construct(ModuleDataSetupInterface $moduleDataSetup)
    {
        $this->moduleDataSetup = $moduleDataSetup;
    }

    public function apply(): void
    {
        $connection = $this->moduleDataSetup->getConnection();
        $locationTable = $this->moduleDataSetup->getTable('zaca_events_location');
        $slotTable = $this->moduleDataSetup->getTable('zaca_events_time_slot');

        if (!$connection->isTableExists($slotTable) || !$connection->isTableExists($locationTable)) {
            return;
        }

        $locationIds = $connection->fetchCol(
            $connection->select()
                ->from($locationTable, ['location_id'])
                ->where('is_active = ?', 1)
        );

        foreach ($locationIds as $locationId) {
            $existing = (int) $connection->fetchOne(
                $connection->select()
                    ->from($slotTable, ['count' => new \Zend_Db_Expr('COUNT(*)')])
                    ->where('location_id = ?', (int) $locationId)
            );
            if ($existing > 0) {
                continue;
            }

            $rows = [];
            foreach (self::DEFAULT_SLOTS as $idx => [$start, $end]) {
                $rows[] = [
                    'location_id' => (int) $locationId,
                    'day_of_week' => null,
                    'start_time' => $start,
                    'end_time' => $end,
                    'sort_order' => ($idx + 1) * 10,
                    'is_active' => 1,
                ];
            }
            $connection->insertMultiple($slotTable, $rows);
        }
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
