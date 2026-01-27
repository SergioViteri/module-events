# Agents Guide - Magento Events Module

This document provides comprehensive instructions for agents working on this Magento 2 module project. This project follows the same patterns and conventions as other modules in the parent `magento` directory (such as `bot`, `box`, `events`, `card`, etc.).

## Project Overview

This is a Magento 2 custom module that follows the standard Zaca module structure. All modules in the `/home/sergio/dev/magento/` directory share common patterns for:
- Module structure and organization
- Admin CRUD implementations
- **Composer-based deployment (ALL installations via Composer, NEVER in `app/code/`)**
- Docker container access

**Critical:** All modules are installed via Composer using path repositories. Modules are developed in `/home/sergio/dev/magento/modulename/` and installed via `composer require`. They are NEVER placed in the `app/code/` directory.

When working on this project, always reference existing modules (especially `bot`, `box`, `events`) to maintain consistency.

## Module Structure Standards

### Directory Structure

All Magento 2 modules in this workspace follow this standard structure:

```
ModuleName/
├── Api/                    # API interfaces (REST/SOAP)
├── Block/                  # Block classes (frontend/backend)
│   └── Adminhtml/          # Admin blocks
├── Console/                # CLI commands
├── Controller/             # Controllers
│   ├── Adminhtml/          # Admin controllers
│   └── [Frontend]/         # Frontend controllers
├── Cron/                   # Cron job classes
├── Data/                   # Data files (instructions, configs, etc.)
├── etc/                    # Module configuration
│   ├── adminhtml/          # Admin-specific config
│   │   ├── menu.xml        # Admin menu items
│   │   └── routes.xml      # Admin routes
│   ├── acl.xml             # Access Control List
│   ├── config.xml          # Module configuration
│   ├── di.xml              # Dependency injection
│   ├── events.xml          # Event observers
│   ├── module.xml          # Module declaration
│   └── webapi.xml          # REST API endpoints
├── Helper/                 # Helper classes
├── i18n/                   # Translation files
│   ├── en_US.csv
│   └── es_ES.csv
├── Logger/                 # Custom logging (if needed)
├── Model/                  # Model classes
│   ├── ResourceModel/      # Resource models
│   │   └── [Entity]/       # Entity-specific resources
│   │       ├── Collection.php
│   │       └── Grid/        # Grid collections
│   │           └── Collection.php
│   └── Config/             # Configuration models
│       ├── Source/          # Source models (dropdowns)
│       └── Backend/        # Backend models
├── Observer/               # Event observers
├── Plugin/                 # Plugin classes
├── Setup/                   # Database setup
│   ├── InstallSchema.php
│   └── UpgradeSchema.php
├── Ui/                     # UI Components
│   └── Component/
│       ├── DataProvider.php
│       ├── Form/
│       │   └── DataProvider.php
│       └── Listing/
│           ├── DataProvider.php
│           └── Column/     # Custom column renderers
├── view/                   # Views and templates
│   ├── adminhtml/          # Admin views
│   │   ├── layout/         # Layout XML files
│   │   ├── templates/      # PHTML templates
│   │   ├── ui_component/   # UI component XML
│   │   └── web/            # Static assets (JS/CSS)
│   └── frontend/           # Frontend views
├── composer.json           # Composer configuration
├── registration.php        # Module registration
└── README.md               # Module documentation
```

### Required Files

Every module must have these core files:

#### 1. `composer.json`

Standard format used across all modules:

```json
{
    "name": "zaca/modulename",
    "description": "Module description",
    "require": {
        "magento/magento-composer-installer": "*"
    },
    "type": "magento2-module",
    "version": "1.0.0",
    "autoload": {
        "files": [ "registration.php" ],
        "psr-4": {
            "Zaca\\ModuleName\\": ""
        }
    }
}
```

**Key points:**
- Vendor name: `zaca`
- Type: `magento2-module`
- PSR-4 autoloading with namespace `Zaca\ModuleName\`
- Include `registration.php` in files array

#### 2. `registration.php`

```php
<?php
\Magento\Framework\Component\ComponentRegistrar::register(
    \Magento\Framework\Component\ComponentRegistrar::MODULE,
    'Zaca_ModuleName',
    __DIR__
);
```

**Naming convention:** `Zaca_ModuleName` (underscore, PascalCase)

#### 3. `etc/module.xml`

```xml
<?xml version="1.0"?>
<config xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" 
        xsi:noNamespaceSchemaLocation="urn:magento:framework:Module/etc/module.xsd">
    <module name="Zaca_ModuleName" setup_version="1.0.0">
    </module>
</config>
```

### Naming Conventions

- **Vendor namespace:** `Zaca`
- **Module name:** PascalCase (e.g., `Bot`, `Events`, `Box`)
- **Module identifier:** `Zaca_ModuleName` (underscore separator)
- **Class namespace:** `Zaca\ModuleName\`
- **Database tables:** `zaca_modulename_entityname` (lowercase, underscores)

## Admin CRUD Implementation Guide

This guide shows how to create a complete admin CRUD following the patterns from the `bot` module's Messages CRUD.

### Step 1: Create Database Schema

Create `Setup/InstallSchema.php`:

```php
<?php
namespace Zaca\ModuleName\Setup;

use Magento\Framework\Setup\InstallSchemaInterface;
use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\Setup\SchemaSetupInterface;
use Magento\Framework\DB\Ddl\Table;

class InstallSchema implements InstallSchemaInterface
{
    public function install(SchemaSetupInterface $setup, ModuleContextInterface $context)
    {
        $installer = $setup;
        $installer->startSetup();

        $table = $installer->getConnection()->newTable(
            $installer->getTable('zaca_modulename_entity')
        )->addColumn(
            'id',
            Table::TYPE_INTEGER,
            null,
            ['identity' => true, 'nullable' => false, 'primary' => true],
            'Entity ID'
        )->addColumn(
            'name',
            Table::TYPE_TEXT,
            255,
            ['nullable' => false],
            'Name'
        )->addColumn(
            'created_at',
            Table::TYPE_TIMESTAMP,
            null,
            ['nullable' => false, 'default' => Table::TIMESTAMP_INIT],
            'Created At'
        )->addColumn(
            'updated_at',
            Table::TYPE_TIMESTAMP,
            null,
            ['nullable' => false, 'default' => Table::TIMESTAMP_INIT_UPDATE],
            'Updated At'
        )->setComment(
            'Entity Table'
        );
        $installer->getConnection()->createTable($table);

        $installer->endSetup();
    }
}
```

### Step 2: Create Model and ResourceModel

#### Model: `Model/Entity.php`

```php
<?php
namespace Zaca\ModuleName\Model;

class Entity extends \Magento\Framework\Model\AbstractModel 
    implements \Magento\Framework\DataObject\IdentityInterface
{
    const CACHE_TAG = 'zaca_modulename_entity';

    protected $_cacheTag = 'zaca_modulename_entity';
    protected $_eventPrefix = 'zaca_modulename_entity';

    protected function _construct()
    {
        $this->_init('Zaca\ModuleName\Model\ResourceModel\Entity');
    }

    public function getIdentities()
    {
        return [self::CACHE_TAG . '_' . $this->getId()];
    }

    public function getDefaultValues()
    {
        $values = [];
        return $values;
    }
}
```

#### ResourceModel: `Model/ResourceModel/Entity.php`

```php
<?php
namespace Zaca\ModuleName\Model\ResourceModel;

class Entity extends \Magento\Framework\Model\ResourceModel\Db\AbstractDb
{
    public function __construct(
        \Magento\Framework\Model\ResourceModel\Db\Context $context
    ) {
        parent::__construct($context);
    }

    protected function _construct()
    {
        $this->_init('zaca_modulename_entity', 'id');
    }
}
```

#### Collection: `Model/ResourceModel/Entity/Collection.php`

```php
<?php
namespace Zaca\ModuleName\Model\ResourceModel\Entity;

class Collection extends \Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection
{
    protected $_idFieldName = 'id';
    protected $_eventPrefix = 'zaca_modulename_entity_collection';
    protected $_eventObject = 'zaca_modulename_entity_collection';

    protected function _construct()
    {
        $this->_init(
            'Zaca\ModuleName\Model\Entity',
            'Zaca\ModuleName\Model\ResourceModel\Entity'
        );
    }
}
```

#### Grid Collection: `Model/ResourceModel/Entity/Grid/Collection.php`

```php
<?php
namespace Zaca\ModuleName\Model\ResourceModel\Entity\Grid;

use Magento\Framework\Data\Collection\Db\FetchStrategyInterface as FetchStrategy;
use Magento\Framework\Data\Collection\EntityFactoryInterface as EntityFactory;
use Magento\Framework\Event\ManagerInterface as EventManager;
use Psr\Log\LoggerInterface as Logger;

class Collection extends \Magento\Framework\View\Element\UiComponent\DataProvider\SearchResult
{
    public function __construct(
        EntityFactory $entityFactory,
        Logger $logger,
        FetchStrategy $fetchStrategy,
        EventManager $eventManager,
        $mainTable = 'zaca_modulename_entity',
        $resourceModel = 'Zaca\ModuleName\Model\ResourceModel\Entity',
        $identifierName = 'id',
        $connectionName = null
    ) {
        parent::__construct(
            $entityFactory,
            $logger,
            $fetchStrategy,
            $eventManager,
            $mainTable,
            $resourceModel,
            $identifierName,
            $connectionName
        );
    }
}
```

### Step 3: Configure Dependency Injection

Add to `etc/di.xml`:

```xml
<?xml version="1.0"?>
<config xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" 
        xsi:noNamespaceSchemaLocation="urn:magento:framework:ObjectManager/etc/config.xsd">
    <type name="Magento\Framework\View\Element\UiComponent\DataProvider\CollectionFactory">
        <arguments>
            <argument name="collections" xsi:type="array">
                <item name="entity_listing_data_source" xsi:type="string">Zaca\ModuleName\Model\ResourceModel\Entity\Grid\Collection</item>
            </argument>
        </arguments>
    </type>
</config>
```

### Step 4: Create Admin Controllers

#### Index Controller: `Controller/Adminhtml/Entity/Index.php`

```php
<?php
namespace Zaca\ModuleName\Controller\Adminhtml\Entity;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\View\Result\PageFactory;

class Index extends Action
{
    protected $resultPageFactory;

    public function __construct(
        Context $context,
        PageFactory $resultPageFactory
    ) {
        parent::__construct($context);
        $this->resultPageFactory = $resultPageFactory;
    }

    public function execute()
    {
        $resultPage = $this->resultPageFactory->create();
        $resultPage->setActiveMenu('Zaca_ModuleName::entity');
        $resultPage->getConfig()->getTitle()->prepend(__('Entities'));

        return $resultPage;
    }

    protected function _isAllowed()
    {
        return $this->_authorization->isAllowed('Zaca_ModuleName::entity');
    }
}
```

#### View Controller: `Controller/Adminhtml/Entity/View.php`

```php
<?php
namespace Zaca\ModuleName\Controller\Adminhtml\Entity;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\View\Result\PageFactory;
use Zaca\ModuleName\Model\EntityFactory;
use Magento\Framework\Exception\NoSuchEntityException;

class View extends Action
{
    protected $resultPageFactory;
    protected $entityFactory;

    public function __construct(
        Context $context,
        PageFactory $resultPageFactory,
        EntityFactory $entityFactory
    ) {
        parent::__construct($context);
        $this->resultPageFactory = $resultPageFactory;
        $this->entityFactory = $entityFactory;
    }

    public function execute()
    {
        $id = $this->getRequest()->getParam('id');
        
        if (!$id) {
            $this->messageManager->addError(__('Entity ID is required.'));
            $resultRedirect = $this->resultRedirectFactory->create();
            return $resultRedirect->setPath('*/*/index');
        }

        try {
            $entity = $this->entityFactory->create()->load($id);
            
            if (!$entity->getId()) {
                throw new NoSuchEntityException(__('Entity with ID "%1" does not exist.', $id));
            }

            $resultPage = $this->resultPageFactory->create();
            $resultPage->setActiveMenu('Zaca_ModuleName::entity');
            $resultPage->getConfig()->getTitle()->prepend(__('View Entity #%1', $id));

            return $resultPage;
        } catch (NoSuchEntityException $e) {
            $this->messageManager->addError($e->getMessage());
            $resultRedirect = $this->resultRedirectFactory->create();
            return $resultRedirect->setPath('*/*/index');
        } catch (\Exception $e) {
            $this->messageManager->addError(__('An error occurred while loading the entity.'));
            $resultRedirect = $this->resultRedirectFactory->create();
            return $resultRedirect->setPath('*/*/index');
        }
    }

    protected function _isAllowed()
    {
        return $this->_authorization->isAllowed('Zaca_ModuleName::entity');
    }
}
```

### Step 5: Configure Routes

Create `etc/adminhtml/routes.xml`:

```xml
<?xml version="1.0"?>
<config xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" 
        xsi:noNamespaceSchemaLocation="urn:magento:framework:App/etc/routes.xsd">
    <router id="admin">
        <route id="zaca_modulename" frontName="zaca_modulename">
            <module name="Zaca_ModuleName"/>
        </route>
    </router>
</config>
```

**Front name convention:** `zaca_modulename` (lowercase, underscores)

### Step 6: Configure ACL

Create `etc/acl.xml`:

```xml
<?xml version="1.0"?>
<config xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
        xsi:noNamespaceSchemaLocation="urn:magento:framework:Acl/etc/acl.xsd">
    <acl>
        <resources>
            <resource id="Magento_Backend::admin">
                <resource id="Zaca_ModuleName::module" title="Module Name" sortOrder="10">
                    <resource id="Zaca_ModuleName::entity" title="Entities" sortOrder="10"/>
                </resource>
            </resource>
        </resources>
    </acl>
</config>
```

### Step 7: Configure Admin Menu

Create `etc/adminhtml/menu.xml`:

```xml
<?xml version="1.0"?>
<config xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" 
        xsi:noNamespaceSchemaLocation="urn:magento:module:Magento_Backend:etc/menu.xsd">
    <menu>
        <add id="Zaca_ModuleName::module" 
             title="Module Name" 
             module="Zaca_ModuleName" 
             sortOrder="100" 
             parent="Magento_Backend::stores"
             resource="Zaca_ModuleName::module"/>
        <add id="Zaca_ModuleName::entity" 
             title="Entities" 
             module="Zaca_ModuleName" 
             sortOrder="10" 
             parent="Zaca_ModuleName::module" 
             action="zaca_modulename/entity/index" 
             resource="Zaca_ModuleName::entity"/>
    </menu>
</config>
```

### Step 8: Create UI Components

#### Listing DataProvider: `Ui/Component/Listing/DataProvider.php`

```php
<?php
namespace Zaca\ModuleName\Ui\Component\Listing;

use Magento\Framework\Api\FilterBuilder;
use Magento\Framework\Api\Search\ReportingInterface;
use Magento\Framework\Api\Search\SearchCriteriaBuilder;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\View\Element\UiComponent\DataProvider\DataProvider as AbstractDataProvider;

class DataProvider extends AbstractDataProvider
{
    public function __construct(
        $name,
        $primaryFieldName,
        $requestFieldName,
        ReportingInterface $reporting,
        SearchCriteriaBuilder $searchCriteriaBuilder,
        RequestInterface $request,
        FilterBuilder $filterBuilder,
        array $meta = [],
        array $data = []
    ) {
        parent::__construct(
            $name,
            $primaryFieldName,
            $requestFieldName,
            $reporting,
            $searchCriteriaBuilder,
            $request,
            $filterBuilder,
            $meta,
            $data
        );
    }

    public function getData()
    {
        $collection = $this->getSearchResult();
        $data = [
            'totalRecords' => $collection->getSize(),
            'items' => []
        ];

        foreach ($collection->getItems() as $item) {
            $data['items'][] = $item->getData();
        }

        return $data;
    }
}
```

#### Form DataProvider: `Ui/Component/Form/DataProvider.php`

```php
<?php
namespace Zaca\ModuleName\Ui\Component\Form;

use Magento\Framework\App\Request\DataPersistorInterface;
use Magento\Framework\App\RequestInterface;
use Zaca\ModuleName\Model\EntityFactory;
use Magento\Ui\DataProvider\AbstractDataProvider;

class DataProvider extends AbstractDataProvider
{
    protected $loadedData;
    protected $dataPersistor;
    protected $entityFactory;
    protected $request;

    public function __construct(
        $name,
        $primaryFieldName,
        $requestFieldName,
        EntityFactory $entityFactory,
        DataPersistorInterface $dataPersistor,
        RequestInterface $request,
        array $meta = [],
        array $data = []
    ) {
        $this->collection = $entityFactory->create()->getCollection();
        $this->dataPersistor = $dataPersistor;
        $this->entityFactory = $entityFactory;
        $this->request = $request;
        parent::__construct($name, $primaryFieldName, $requestFieldName, $meta, $data);
    }

    public function getData()
    {
        if (isset($this->loadedData)) {
            return $this->loadedData;
        }

        $this->loadedData = [];
        
        $id = $this->request->getParam($this->getRequestFieldName());
        if ($id) {
            $entity = $this->entityFactory->create()->load($id);
            if ($entity->getId()) {
                $this->loadedData[$entity->getId()] = $entity->getData();
            }
        }

        $data = $this->dataPersistor->get('zaca_modulename_entity');
        if (!empty($data)) {
            $entity = $this->collection->getNewEmptyItem();
            $entity->setData($data);
            $this->loadedData[$entity->getId()] = $entity->getData();
            $this->dataPersistor->clear('zaca_modulename_entity');
        }

        return $this->loadedData;
    }
}
```

#### Listing UI Component: `view/adminhtml/ui_component/entity_listing.xml`

```xml
<?xml version="1.0" encoding="UTF-8"?>
<listing xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" 
        xsi:noNamespaceSchemaLocation="urn:magento:module:Magento_Ui:etc/ui_configuration.xsd">
    <argument name="data" xsi:type="array">
        <item name="js_config" xsi:type="array">
            <item name="provider" xsi:type="string">entity_listing.entity_listing_data_source</item>
        </item>
    </argument>
    <settings>
        <spinner>entity_columns</spinner>
        <deps>
            <dep>entity_listing.entity_listing_data_source</dep>
        </deps>
    </settings>
    <dataSource name="entity_listing_data_source" component="Magento_Ui/js/grid/provider">
        <settings>
            <storageConfig>
                <param name="indexField" xsi:type="string">id</param>
            </storageConfig>
            <updateUrl path="mui/index/render"/>
        </settings>
        <aclResource>Zaca_ModuleName::entity</aclResource>
        <dataProvider class="Zaca\ModuleName\Ui\Component\Listing\DataProvider" 
                      name="entity_listing_data_source">
            <settings>
                <requestFieldName>id</requestFieldName>
                <primaryFieldName>id</primaryFieldName>
            </settings>
        </dataProvider>
    </dataSource>
    <listingToolbar name="listing_top">
        <settings>
            <sticky>true</sticky>
        </settings>
        <bookmark name="bookmarks"/>
        <columnsControls name="columns_controls"/>
        <filterSearch name="fulltext"/>
        <filters name="listing_filters"/>
        <paging name="listing_paging"/>
    </listingToolbar>
    <columns name="entity_columns">
        <settings>
            <childDefaults>
                <param name="storageConfig" xsi:type="array">
                    <item name="provider" xsi:type="string">entity_listing.entity_listing.listing_top.bookmarks</item>
                    <item name="root" xsi:type="string">columns.${ $.index }</item>
                    <item name="namespace" xsi:type="string">current.${ $.storageConfig.root }</item>
                </param>
            </childDefaults>
        </settings>
        <column name="id">
            <settings>
                <filter>textRange</filter>
                <label translate="true">ID</label>
                <sorting>desc</sorting>
            </settings>
        </column>
        <column name="name">
            <settings>
                <filter>text</filter>
                <label translate="true">Name</label>
            </settings>
        </column>
        <column name="created_at" class="Magento\Ui\Component\Listing\Columns\Date" 
                component="Magento_Ui/js/grid/columns/date">
            <settings>
                <filter>dateRange</filter>
                <dataType>date</dataType>
                <label translate="true">Created At</label>
            </settings>
        </column>
        <column name="actions" class="Magento\Ui\Component\Listing\Columns\Actions" 
                component="Magento_Ui/js/grid/columns/actions">
            <settings>
                <indexField>id</indexField>
            </settings>
            <actions>
                <action name="view">
                    <settings>
                        <url path="zaca_modulename/entity/view">
                            <param name="id">${ $.$data.row.id }</param>
                        </url>
                        <type>row</type>
                        <label translate="true">View</label>
                    </settings>
                </action>
            </actions>
        </column>
    </columns>
</listing>
```

#### Form UI Component: `view/adminhtml/ui_component/entity_view_form.xml`

```xml
<?xml version="1.0" encoding="UTF-8"?>
<form xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" 
      xsi:noNamespaceSchemaLocation="urn:magento:module:Magento_Ui:etc/ui_configuration.xsd">
    <argument name="data" xsi:type="array">
        <item name="js_config" xsi:type="array">
            <item name="provider" xsi:type="string">entity_view_form.entity_view_form_data_source</item>
        </item>
        <item name="label" xsi:type="string" translate="true">Entity Information</item>
        <item name="reverseMetadata" xsi:type="boolean">true</item>
    </argument>
    <settings>
        <buttons>
            <button name="back">
                <url path="*/*/index"/>
                <class>back</class>
                <label translate="true">Back</label>
            </button>
        </buttons>
        <namespace>entity_view_form</namespace>
        <dataScope>data</dataScope>
        <deps>
            <dep>entity_view_form.entity_view_form_data_source</dep>
        </deps>
    </settings>
    <dataSource name="entity_view_form_data_source">
        <argument name="data" xsi:type="array">
            <item name="js_config" xsi:type="array">
                <item name="component" xsi:type="string">Magento_Ui/js/form/provider</item>
            </item>
        </argument>
        <settings>
            <submitUrl path="zaca_modulename/entity/save"/>
        </settings>
        <dataProvider class="Zaca\ModuleName\Ui\Component\Form\DataProvider" 
                      name="entity_view_form_data_source">
            <settings>
                <requestFieldName>id</requestFieldName>
                <primaryFieldName>id</primaryFieldName>
            </settings>
        </dataProvider>
    </dataSource>
    <fieldset name="general">
        <settings>
            <label translate="true">Entity Details</label>
        </settings>
        <field name="id" formElement="input">
            <settings>
                <dataType>text</dataType>
                <visible>true</visible>
                <disabled>true</disabled>
                <label translate="true">ID</label>
            </settings>
        </field>
        <field name="name" formElement="input">
            <settings>
                <dataType>text</dataType>
                <visible>true</visible>
                <disabled>true</disabled>
                <label translate="true">Name</label>
            </settings>
        </field>
        <field name="created_at" formElement="date">
            <settings>
                <dataType>text</dataType>
                <visible>true</visible>
                <disabled>true</disabled>
                <label translate="true">Created At</label>
            </settings>
        </field>
    </fieldset>
</form>
```

### Step 9: Create Layout Files

#### Index Layout: `view/adminhtml/layout/zaca_modulename_entity_index.xml`

```xml
<?xml version="1.0"?>
<page xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" 
      xsi:noNamespaceSchemaLocation="urn:magento:framework:View/Layout/etc/page_configuration.xsd">
    <body>
        <referenceContainer name="content">
            <uiComponent name="entity_listing"/>
        </referenceContainer>
    </body>
</page>
```

#### View Layout: `view/adminhtml/layout/zaca_modulename_entity_view.xml`

```xml
<?xml version="1.0"?>
<page xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" 
      xsi:noNamespaceSchemaLocation="urn:magento:framework:View/Layout/etc/page_configuration.xsd">
    <update handle="styles"/>
    <body>
        <referenceContainer name="content">
            <uiComponent name="entity_view_form"/>
        </referenceContainer>
    </body>
</page>
```

**Layout naming convention:** `{frontname}_{controller}_{action}.xml`

## Composer Deployment

**IMPORTANT: All module installations MUST be done via Composer. Modules are NEVER placed in the `app/code` directory.**

All modules in this workspace are installed using Composer path repositories. The `magento/magento-composer-installer` package handles the installation to the correct location (typically `vendor/` or as configured in the Magento installation).

### Composer.json Structure

All modules use this standard `composer.json` format:

```json
{
    "name": "zaca/modulename",
    "description": "Module description",
    "require": {
        "magento/magento-composer-installer": "*"
    },
    "type": "magento2-module",
    "version": "1.0.0",
    "autoload": {
        "files": [ "registration.php" ],
        "psr-4": {
            "Zaca\\ModuleName\\": ""
        }
    }
}
```

### Path Repository Setup

In the main Magento `composer.json`, add a path repository:

```json
{
    "repositories": [
        {
            "type": "path",
            "url": "/home/sergio/dev/magento/modulename"
        }
    ]
}
```

### Installation Commands

**All installations must be done via Composer. Never copy modules to `app/code/`.**

After setting up the path repository, install the module:

```bash
# Install via composer (this installs to vendor/ or configured location, NOT app/code/)
composer require zaca/modulename:*

# Enable the module
bin/magento module:enable Zaca_ModuleName

# Run database setup/upgrade
bin/magento setup:upgrade

# Compile dependency injection
bin/magento setup:di:compile

# Deploy static content (if needed)
bin/magento setup:static-content:deploy

# Clear cache
bin/magento cache:flush
```

**Note:** The `magento/magento-composer-installer` package automatically handles the module installation. Modules are installed via Composer's autoloading mechanism, not by copying files to `app/code/`.

### Version Management

- Update version in `composer.json` and `etc/module.xml` when making changes
- Use semantic versioning (e.g., 1.0.0, 1.0.1, 1.1.0)
- After version changes, run `composer update zaca/modulename` in the main Magento installation

### Deployment Workflow

**Remember: All deployments are Composer-based. Never manually copy files to `app/code/`.**

1. Make changes to the module in `/home/sergio/dev/magento/modulename/`
2. Update version numbers if needed
3. In the Magento installation (or Docker container), run:
   ```bash
   # Update via composer (installs from path repository)
   composer update zaca/modulename
   
   # Run Magento setup commands
   bin/magento setup:upgrade
   bin/magento setup:di:compile
   bin/magento cache:flush
   ```

The module will be installed by Composer to the appropriate location (typically `vendor/zaca/modulename/` or as configured by the Magento Composer installer).

## Docker Container Access

### Container Information

- **Magento container:** `magento-web24`
- **MySQL container:** `magento-demo-mysql`

### Accessing Containers

If containers are not running we can start them with
```bash
# Run all magento containers (magento, mysql, redis, opensearch and varnish)
/home/sergio/dev/docker/start.sh
```

#### Access Magento Container

```bash
# Access the Magento container shell
docker exec -it magento-web24 bash

# Run Magento commands from host
docker exec -it magento-web24 bin/magento cache:flush

# Run composer commands
docker exec -it magento-web24 composer require zaca/modulename:*
```

#### Access MySQL Container

```bash
# Access MySQL container shell
docker exec -it magento-demo-mysql bash

# Connect to MySQL from host
docker exec -it magento-demo-mysql mysql -u root -p

# Run MySQL commands
docker exec -it magento-demo-mysql mysql -u root -p -e "SHOW DATABASES;"
```

### Common Docker Commands for Development

```bash
# View container logs
docker logs magento-web24
docker logs magento-demo-mysql

# Restart containers
docker restart magento-web24
docker restart magento-demo-mysql

# Check container status
docker ps | grep magento

# Execute Magento CLI commands
docker exec -it magento-web24 bin/magento setup:upgrade
docker exec -it magento-web24 bin/magento setup:di:compile
docker exec -it magento-web24 bin/magento cache:flush
docker exec -it magento-web24 bin/magento module:enable Zaca_ModuleName
docker exec -it magento-web24 bin/magento module:disable Zaca_ModuleName

# Execute Composer commands
docker exec -it magento-web24 composer require zaca/modulename:*
docker exec -it magento-web24 composer update zaca/modulename
docker exec -it magento-web24 composer install
```

### File System Access

The Magento installation files are typically mounted as volumes. Check docker-compose.yml or container configuration to find the exact mount points.

## Best Practices

### Code Organization

1. **Follow PSR-4 autoloading:** All classes must follow the namespace structure defined in `composer.json`
2. **Use dependency injection:** Always use constructor injection, never use object manager directly
3. **Follow Magento coding standards:** Use Magento's coding standards and best practices
4. **Keep controllers thin:** Business logic should be in models, services, or repositories

### Naming Conventions

1. **Database tables:** `zaca_modulename_entityname` (lowercase, underscores)
2. **Front names:** `zaca_modulename` (lowercase, underscores)
3. **ACL resources:** `Zaca_ModuleName::resource` (PascalCase with underscore)
4. **Menu IDs:** Match ACL resource IDs
5. **UI component names:** `entity_listing`, `entity_view_form` (lowercase, underscores)

### Module Development

1. **Always use Composer:** All modules MUST be installed via Composer. NEVER place modules in `app/code/` directory
2. **Always create InstallSchema:** Even if empty initially, include the file
3. **Use factories:** Always use factories for model instantiation in controllers
4. **Implement IdentityInterface:** For models that need cache invalidation
5. **Add translations:** Include `i18n/en_US.csv` and `i18n/es_ES.csv` files
6. **Document in README:** Include installation and usage instructions (always mention Composer installation)

### Testing

1. **Test in Docker:** Always test changes in the Docker environment
2. **Clear cache:** After any configuration changes, clear cache
3. **Run setup:upgrade:** After schema changes, always run `setup:upgrade`
4. **Check logs:** Monitor container logs for errors

### Reference Modules

When in doubt, reference these modules for examples:
- **`bot`** - Complete CRUD example (Messages entity)
- **`events`** - Event management and registrations
- **`box`** - Subscription management
- **`card`** - Card management features

## Additional Resources

- Magento 2 Developer Documentation: https://devdocs.magento.com/
- Magento 2 UI Components Guide: https://devdocs.magento.com/guides/v2.4/ui_comp_guide/
- Magento 2 Module Development: https://devdocs.magento.com/guides/v2.4/extension-dev-guide/

---

**Last Updated:** Based on patterns from existing modules in `/home/sergio/dev/magento/`
