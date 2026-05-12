<?php
/**
 * Backfill url_key for existing locations based on their name.
 *
 * @category    Zacatrus
 * @package     Zaca_Events
 * @author      Zacatrus
 */

namespace Zaca\Events\Setup\Patch\Data;

use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\Framework\Setup\Patch\DataPatchInterface;

class BackfillLocationUrlKeys implements DataPatchInterface
{
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
            $connection->select()->from($table, ['location_id', 'name', 'url_key'])
        );

        $usedSlugs = [];
        foreach ($rows as $row) {
            $existing = trim((string) ($row['url_key'] ?? ''));
            if ($existing !== '') {
                $usedSlugs[strtolower($existing)] = true;
            }
        }

        foreach ($rows as $row) {
            $existing = trim((string) ($row['url_key'] ?? ''));
            if ($existing !== '') {
                continue;
            }
            $base = self::slugify((string) $row['name']);
            if ($base === '') {
                $base = 'tienda-' . (int) $row['location_id'];
            }
            $slug = $base;
            $i = 2;
            while (isset($usedSlugs[strtolower($slug)])) {
                $slug = $base . '-' . $i++;
            }
            $usedSlugs[strtolower($slug)] = true;
            $connection->update(
                $table,
                ['url_key' => $slug],
                ['location_id = ?' => (int) $row['location_id']]
            );
        }
    }

    public static function slugify(string $value): string
    {
        $value = trim($value);
        if ($value === '') {
            return '';
        }
        if (function_exists('iconv')) {
            $ascii = @iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $value);
            if (is_string($ascii) && $ascii !== '') {
                $value = $ascii;
            }
        }
        $value = strtolower($value);
        $value = preg_replace('/[^a-z0-9]+/', '-', $value) ?? '';
        $value = trim($value, '-');
        // Drop the redundant "zacatrus-" prefix in location slugs.
        if (strpos($value, 'zacatrus-') === 0) {
            $value = substr($value, strlen('zacatrus-'));
        }
        return $value;
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
