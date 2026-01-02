<?php
/**
 * Zacatrus Events Setup Patch - Rename event_id to meet_id in registration table
 *
 * @category    Zacatrus
 * @package     Zaca_Events
 * @author      Zacatrus
 */

namespace Zaca\Events\Setup\Patch\Schema;

use Magento\Framework\Setup\Patch\SchemaPatchInterface;
use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\Framework\DB\Adapter\AdapterInterface;

class RenameRegistrationEventIdToMeetId implements SchemaPatchInterface
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
        $tableName = $this->moduleDataSetup->getTable('zaca_events_registration');

        // Always try to drop old foreign key constraints first, even if column was already renamed
        $oldConstraintNames = [
            'ZACA_EVENTS_REGISTRATION_EVENT_ID_ZACA_EVENTS_EVENT_EVENT_ID',
            'FK_ZACATRUS_EVENTS_REGISTRATION_EVENT_ID',
            'FK_ZACA_EVENTS_REGISTRATION_EVENT_ID',
            'FK_ZACATRUS_EVENTS_REGISTRATION_MEET_ID'
        ];

        foreach ($oldConstraintNames as $constraintName) {
            try {
                $connection->dropForeignKey($tableName, $constraintName);
            } catch (\Exception $e) {
                // Constraint doesn't exist, continue
            }
        }

        // Also drop any foreign keys that reference event_id column
        $foreignKeys = $connection->getForeignKeys($tableName);
        foreach ($foreignKeys as $foreignKey) {
            if (isset($foreignKey['COLUMN_NAME']) && $foreignKey['COLUMN_NAME'] === 'event_id') {
                try {
                    $connection->dropForeignKey($tableName, $foreignKey['FK_NAME']);
                } catch (\Exception $e) {
                    // Constraint doesn't exist or already dropped, continue
                }
            }
        }

        // Check if event_id column exists and meet_id doesn't
        if ($connection->tableColumnExists($tableName, 'event_id') && 
            !$connection->tableColumnExists($tableName, 'meet_id')) {

            // Drop indexes that reference event_id
            $indexes = $connection->getIndexList($tableName);
            foreach ($indexes as $indexName => $indexData) {
                if (in_array('event_id', $indexData['COLUMNS_LIST'])) {
                    try {
                        $connection->dropIndex($tableName, $indexName);
                    } catch (\Exception $e) {
                        // Index doesn't exist, continue
                    }
                }
            }

            // Rename the column
            $connection->changeColumn(
                $tableName,
                'event_id',
                'meet_id',
                [
                    'type' => \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
                    'unsigned' => true,
                    'nullable' => false,
                    'comment' => 'Meet ID'
                ]
            );

            // Recreate foreign key constraint
            $connection->addForeignKey(
                'FK_ZACA_EVENTS_REGISTRATION_MEET_ID',
                $tableName,
                'meet_id',
                $this->moduleDataSetup->getTable('zaca_events_meet'),
                'meet_id',
                \Magento\Framework\DB\Ddl\Table::ACTION_CASCADE
            );

            // Recreate indexes
            $connection->addIndex(
                $tableName,
                'IDX_ZACA_EVENTS_REGISTRATION_MEET_ID',
                ['meet_id']
            );

            // Recreate unique constraint for meet_id and customer_id
            $connection->addIndex(
                $tableName,
                'UNQ_ZACA_EVENTS_REGISTRATION_MEET_CUSTOMER',
                ['meet_id', 'customer_id'],
                \Magento\Framework\DB\Adapter\AdapterInterface::INDEX_TYPE_UNIQUE
            );
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

