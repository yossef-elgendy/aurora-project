<?php
/**
 * Copyright © Escooter. All rights reserved.
 * See LICENSE.txt for license details.
 */

namespace Escooter\Migration\Setup\Patch\Data;

use Magento\Framework\Setup\Patch\DataPatchInterface;
use Magento\Framework\Setup\Patch\PatchVersionInterface;
use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\Store\Model\WebsiteFactory;
use Magento\Store\Model\GroupFactory;
use Magento\Store\Model\StoreFactory;
use Magento\Store\Model\ResourceModel\Website as WebsiteResource;
use Magento\Store\Model\ResourceModel\Group as GroupResource;
use Magento\Store\Model\ResourceModel\Store as StoreResource;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Catalog\Api\CategoryRepositoryInterface;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\App\Config\Storage\WriterInterface;

/**
 * Data patch to update main website to UK website and create EU website with their respective stores
 */
class AddWebsitesAndStores implements DataPatchInterface
{
    private const ROOT_CATEGORY_ID = 2;

    /**
     * @var ModuleDataSetupInterface
     */
    private $moduleDataSetup;

    /**
     * @var WebsiteFactory
     */
    private $websiteFactory;

    /**
     * @var GroupFactory
     */
    private $groupFactory;

    /**
     * @var StoreFactory
     */
    private $storeFactory;

    /**
     * @var WebsiteResource
     */
    private $websiteResource;

    /**
     * @var GroupResource
     */
    private $groupResource;

    /**
     * @var StoreResource
     */
    private $storeResource;

    /**
     * @var StoreManagerInterface
     */
    private $storeManager;

    /**
     * @var CategoryRepositoryInterface
     */
    private $categoryRepository;

    /**
     * @var WriterInterface
     */
    private $configWriter;

    /**
     * Configuration paths
     */
    private const CONFIG_PATH_CURRENCY_BASE = 'currency/options/base';
    private const CONFIG_PATH_CURRENCY_DEFAULT = 'currency/options/default';
    private const CONFIG_PATH_CURRENCY_ALLOW = 'currency/options/allow';

    /**
     * @param ModuleDataSetupInterface $moduleDataSetup
     * @param WebsiteFactory $websiteFactory
     * @param GroupFactory $groupFactory
     * @param StoreFactory $storeFactory
     * @param WebsiteResource $websiteResource
     * @param GroupResource $groupResource
     * @param StoreResource $storeResource
     * @param StoreManagerInterface $storeManager
     * @param CategoryRepositoryInterface $categoryRepository
     * @param WriterInterface $configWriter
     */
    public function __construct(
        ModuleDataSetupInterface $moduleDataSetup,
        WebsiteFactory $websiteFactory,
        GroupFactory $groupFactory,
        StoreFactory $storeFactory,
        WebsiteResource $websiteResource,
        GroupResource $groupResource,
        StoreResource $storeResource,
        StoreManagerInterface $storeManager,
        CategoryRepositoryInterface $categoryRepository,
        WriterInterface $configWriter
    ) {
        $this->moduleDataSetup = $moduleDataSetup;
        $this->websiteFactory = $websiteFactory;
        $this->groupFactory = $groupFactory;
        $this->storeFactory = $storeFactory;
        $this->websiteResource = $websiteResource;
        $this->groupResource = $groupResource;
        $this->storeResource = $storeResource;
        $this->storeManager = $storeManager;
        $this->categoryRepository = $categoryRepository;
        $this->configWriter = $configWriter;
    }

    /**
     * {@inheritdoc}
     */
    public function apply()
    {
        $this->moduleDataSetup->getConnection()->startSetup();

        try {
            // Get the default root category
            $rootCategory = $this->categoryRepository->get(SELF::ROOT_CATEGORY_ID);

            // Update main website (ID 1) to become UK website
            $ukWebsite = $this->updateMainWebsiteToUK();

            // Create EU Website
            $euWebsite = $this->createWebsite('eu_website', 'EU Website', false);

            // Update main store group to become UK Store Group
            $ukGroup = $this->updateMainStoreGroupToUK($ukWebsite, $rootCategory->getId());

            // Create EU Store Group
            $euGroup = $this->createStoreGroup('eu_group', 'EU Store Group', $euWebsite, $rootCategory->getId());

            // Update default store to become English store
            $this->updateDefaultStoreToEnglish($ukWebsite, $ukGroup);

            // Create Irish store
            $this->createStore('ie', 'Irish Store', $ukWebsite, $ukGroup, 20);

            // Create EU Stores (Italian and French)
            $this->createStore('it', 'Italian Store', $euWebsite, $euGroup, 10);
            $this->createStore('fr', 'French Store', $euWebsite, $euGroup, 20);

            // Set website-level currency
            $this->setWebsiteCurrency($ukWebsite, 'GBP');
            $this->setWebsiteCurrency($euWebsite, 'EUR');

            // Reinitialize stores
            $this->storeManager->reinitStores();
        } catch (\Exception $e) {
            // Log error if needed
            throw new \Exception('Error creating websites and stores: ' . $e->getMessage());
        }

        $this->moduleDataSetup->getConnection()->endSetup();
    }

    /**
     * Create a website
     *
     * @param string $code
     * @param string $name
     * @param bool $isDefault
     * @return \Magento\Store\Model\Website
     * @throws \Exception
     */
    private function createWebsite($code, $name, $isDefault = false)
    {
        $website = $this->websiteFactory->create();
        $website->setCode($code)
            ->setName($name)
            ->setIsDefault($isDefault ? 1 : 0);

        $this->websiteResource->save($website);
        return $website;
    }

    /**
     * Create a store group
     *
     * @param string $code
     * @param string $name
     * @param \Magento\Store\Model\Website $website
     * @param int $rootCategoryId
     * @return \Magento\Store\Model\Group
     * @throws \Exception
     */
    private function createStoreGroup($code, $name, $website, $rootCategoryId)
    {
        $group = $this->groupFactory->create();
        $group->setCode($code)
            ->setName($name)
            ->setWebsiteId($website->getId())
            ->setRootCategoryId($rootCategoryId);

        $this->groupResource->save($group);
        return $group;
    }

    /**
     * Create a store
     *
     * @param string $code
     * @param string $name
     * @param \Magento\Store\Model\Website $website
     * @param \Magento\Store\Model\Group $group
     * @param int $sortOrder
     * @return \Magento\Store\Model\Store
     * @throws \Exception
     */
    private function createStore($code, $name, $website, $group, $sortOrder)
    {
        $store = $this->storeFactory->create();
        $store->setCode($code)
            ->setName($name)
            ->setWebsiteId($website->getId())
            ->setGroupId($group->getId())
            ->setSortOrder($sortOrder)
            ->setIsActive(1);

        $this->storeResource->save($store);

        return $store;
    }

    /**
     * Update main website to become UK website
     *
     * @return \Magento\Store\Model\Website
     * @throws \Exception
     */
    private function updateMainWebsiteToUK()
    {
        // Load the main website (ID 1)
        $website = $this->websiteFactory->create();
        $this->websiteResource->load($website, 1);

        if (!$website->getId()) {
            throw new \Exception('Main website not found');
        }

        // Update website properties
        $website->setCode('uk_main')
            ->setName('UK Website')
            ->setIsDefault(1);

        $this->websiteResource->save($website);
        return $website;
    }

    /**
     * Update main store group to become UK Store Group
     *
     * @param \Magento\Store\Model\Website $website
     * @param int $rootCategoryId
     * @return \Magento\Store\Model\Group
     * @throws \Exception
     */
    private function updateMainStoreGroupToUK($website, $rootCategoryId)
    {
        // Load the main store group (ID 1)
        $group = $this->groupFactory->create();
        $this->groupResource->load($group, 1);

        if (!$group->getId()) {
            throw new \Exception('Main store group not found');
        }

        // Update group properties
        $group->setCode('uk_main_group')
            ->setName('UK Store Group')
            ->setWebsiteId($website->getId())
            ->setRootCategoryId($rootCategoryId);

        $this->groupResource->save($group);
        return $group;
    }

    /**
     * Update default store to become English store
     *
     * @param \Magento\Store\Model\Website $website
     * @param \Magento\Store\Model\Group $group
     * @return \Magento\Store\Model\Store
     * @throws \Exception
     */
    private function updateDefaultStoreToEnglish($website, $group)
    {
        // Load the default store (ID 1)
        $store = $this->storeFactory->create();
        $this->storeResource->load($store, 1);

        if (!$store->getId()) {
            throw new \Exception('Default store not found');
        }

        // Update store properties
        $store->setCode('en')
            ->setName('English Store')
            ->setWebsiteId($website->getId())
            ->setGroupId($group->getId())
            ->setSortOrder(10)
            ->setIsActive(1);

        $this->storeResource->save($store);

        return $store;
    }

    /**
     * Set currency for a website using ConfigWriter
     *
     * @param \Magento\Store\Model\Website $website
     * @param string $currency
     * @return void
     */
    private function setWebsiteCurrency($website, $currency)
    {
        // Set currency in website configuration using ConfigWriter
        $this->configWriter->save(self::CONFIG_PATH_CURRENCY_BASE, $currency, 'websites', $website->getId());
        $this->configWriter->save(self::CONFIG_PATH_CURRENCY_DEFAULT, $currency, 'websites', $website->getId());
        $this->configWriter->save(self::CONFIG_PATH_CURRENCY_ALLOW, $currency, 'websites', $website->getId());
    }

    /**
     * {@inheritdoc}
     */
    public static function getDependencies()
    {
        return [];
    }


    /**
     * {@inheritdoc}
     */
    public function getAliases()
    {
        return [];
    }
}
