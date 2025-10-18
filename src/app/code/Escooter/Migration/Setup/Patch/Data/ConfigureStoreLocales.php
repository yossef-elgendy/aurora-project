<?php
/**
 * Copyright © Escooter. All rights reserved.
 * See LICENSE.txt for license details.
 */

namespace Escooter\Migration\Setup\Patch\Data;

use Magento\Framework\Setup\Patch\DataPatchInterface;
use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\App\Config\Storage\WriterInterface;

/**
 * Data patch to configure locales for each store
 */
class ConfigureStoreLocales implements DataPatchInterface
{
    /**
     * Store code to locale mapping
     */
    private const STORE_LOCALES = [
        'en' => 'en_GB',  // English Store - UK locale
        'ie' => 'en_IE', // Irish Store - Irish locale
        'fr' => 'fr_FR', // French Store - French locale
        'it' => 'it_IT'  // Italian Store - Italian locale
    ];

    /**
     * Configuration path for locale
     */
    private const CONFIG_PATH_LOCALE = 'general/locale/code';

    /**
     * @var ModuleDataSetupInterface
     */
    private $moduleDataSetup;

    /**
     * @var StoreManagerInterface
     */
    private $storeManager;

    /**
     * @var WriterInterface
     */
    private $configWriter;

    /**
     * @param ModuleDataSetupInterface $moduleDataSetup
     * @param StoreManagerInterface $storeManager
     * @param WriterInterface $configWriter
     */
    public function __construct(
        ModuleDataSetupInterface $moduleDataSetup,
        StoreManagerInterface $storeManager,
        WriterInterface $configWriter
    ) {
        $this->moduleDataSetup = $moduleDataSetup;
        $this->storeManager = $storeManager;
        $this->configWriter = $configWriter;
    }

    /**
     * @inheritdoc
     */
    public function apply()
    {
        $this->moduleDataSetup->getConnection()->startSetup();

        try {
            foreach (self::STORE_LOCALES as $storeCode => $locale) {
                $this->configureStoreLocale($storeCode, $locale);
            }

            // Reinitialize stores
            $this->storeManager->reinitStores();

        } catch (\Exception $e) {
            throw new \Exception('Error configuring store locales: ' . $e->getMessage());
        }

        $this->moduleDataSetup->getConnection()->endSetup();
    }

    /**
     * Configure locale for a specific store
     *
     * @param string $storeCode
     * @param string $locale
     * @return void
     * @throws NoSuchEntityException
     */
    private function configureStoreLocale($storeCode, $locale)
    {
        try {
            $store = $this->storeManager->getStore($storeCode);
            $storeId = $store->getId();

            // Set locale for the store
            $this->configWriter->save(
                self::CONFIG_PATH_LOCALE,
                $locale,
                'stores',
                $storeId
            );

        } catch (NoSuchEntityException $e) {
            throw new \Exception("Store with code '{$storeCode}' not found: " . $e->getMessage());
        }
    }

    /**
     * @inheritdoc
     */
    public static function getDependencies()
    {
        return [
            AddWebsitesAndStores::class
        ];
    }

    /**
     * @inheritdoc
     */
    public function getAliases()
    {
        return [];
    }
}
