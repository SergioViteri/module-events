<?php
/**
 * Zacatrus Events Setup Patch - Remove old store_id constraint from meet table
 *
 * @category    Zacatrus
 * @package     Zaca_Events
 * @author      Zacatrus
 */

namespace Zaca\Events\Setup\Patch\Schema;

use Magento\Framework\Setup\Patch\SchemaPatchInterface;
use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\Framework\DB\Adapter\AdapterInterface;

class RemoveMeetStoreConstraint implements SchemaPatchInterface
{
    /**
     * @var ModuleDataSetupInterface
     */
    private $moduleDataSetup;

    /**
     * @param ModuleDataSetupInterface $moduleDataSetup
     */
    public function __construct(
        ModuleDataSetupInterface $moduleDataSetup
    ) {
        $this->moduleDataSetup = $moduleDataSetup;
    }

    /**
     * @inheritdoc
     */
    public function apply()
    {
        $this->moduleDataSetup->getConnection()->startSetup();

        $connection = $this->moduleDataSetup->getConnection();
        $tableName = $this->moduleDataSetup->getTable('zaca_events_meet');

        // Get all foreign keys for the table
        $foreignKeys = $connection->getForeignKeys($tableName);
        
        // Drop any foreign key that references store_id
        foreach ($foreignKeys as $foreignKey) {
            if (isset($foreignKey['COLUMN_NAME']) && $foreignKey['COLUMN_NAME'] === 'store_id') {
                try {
                    $connection->dropForeignKey($tableName, $foreignKey['FK_NAME']);
                } catch (\Exception $e) {
                    // Constraint doesn't exist or already dropped, continue
                }
            }
        }

        // Also try dropping by common constraint names
        $constraintNames = [
            'ZACA_EVENTS_MEET_STORE_ID_ZACATRUS_EVENTS_STORE_STORE_ID',
            'FK_ZACA_EVENTS_MEET_STORE_ID',
            'FK_ZACATRUS_EVENTS_EVENT_STORE_ID'
        ];

        foreach ($constraintNames as $constraintName) {
            try {
                $connection->dropForeignKey($tableName, $constraintName);
            } catch (\Exception $e) {
                // Constraint doesn't exist, continue
            }
        }

        // Drop store_id column if it exists
        if ($connection->tableColumnExists($tableName, 'store_id')) {
            $connection->dropColumn($tableName, 'store_id');
        }

        $this->moduleDataSetup->getConnection()->endSetup();
    }

    /**
     * @inheritdoc
     */
    public static function getDependencies()
    {
        return [];
    }

    /**
     * @inheritdoc
     */
    public function getAliases()
    {
        return [];
    }
}

