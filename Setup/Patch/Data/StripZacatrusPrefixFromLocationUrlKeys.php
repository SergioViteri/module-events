<?php
/**
 * Strip the "zacatrus-" prefix from existing location url_keys so that
 * URLs read as /eventos/madrid-chamberi instead of /eventos/zacatrus-madrid-chamberi.
 *
 * @category    Zacatrus
 * @package     Zaca_Events
 * @author      Zacatrus
 */

namespace Zaca\Events\Setup\Patch\Data;

use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\Framework\Setup\Patch\DataPatchInterface;

class StripZacatrusPrefixFromLocationUrlKeys implements DataPatchInterface
{
    private const PREFIX = 'zacatrus-';

    private ModuleDataSetupInterface $moduleDataSetup;

    public function __construct(ModuleDataSetupInterface $moduleDataSetup)
    {
        $this->moduleDataSetup = $moduleDataSetup;
    }

    public function apply(): void
    {
        $connection = $this->moduleDataSetup->getConnection();
        $table = $this->moduleDataSetup->getTable('zaca_events_location');

        if (!$connection->isTableExists($table) || !$connection->tableColumnExists($table, 'url_key')) {
            return;
        }

        $rows = $connection->fetchAll(
            $connection->select()->from($table, ['location_id', 'url_key'])
        );

        $usedSlugs = [];
        $candidates = [];
        foreach ($rows as $row) {
            $current = (string) ($row['url_key'] ?? '');
            $usedSlugs[strtolower($current)] = true;
            if ($current !== '' && strpos($current, self::PREFIX) === 0) {
                $candidates[] = [
                    'location_id' => (int) $row['location_id'],
                    'current' => $current,
                    'desired' => substr($current, strlen(self::PREFIX)),
                ];
            }
        }

        foreach ($candidates as $c) {
            $desired = $c['desired'];
            if ($desired === '') {
                continue;
            }
            // Free the old slug before we evaluate collisions on the desired one.
            unset($usedSlugs[strtolower($c['current'])]);

            $finalSlug = $desired;
            $i = 2;
            while (isset($usedSlugs[strtolower($finalSlug)])) {
                $finalSlug = $desired . '-' . $i++;
            }
            $usedSlugs[strtolower($finalSlug)] = true;

            $connection->update(
                $table,
                ['url_key' => $finalSlug],
                ['location_id = ?' => $c['location_id']]
            );
        }
    }

    public static function getDependencies(): array
    {
        return [BackfillLocationUrlKeys::class];
    }

    public function getAliases(): array
    {
        return [];
    }
}
