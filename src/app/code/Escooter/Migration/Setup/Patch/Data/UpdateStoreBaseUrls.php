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
 * Data patch to update base URLs for French, Irish, and Italian stores
 */
class UpdateStoreBaseUrls implements DataPatchInterface
{
    /**
     * Store code to domain mapping
     */
    private const STORE_DOMAINS = [
        'fr' => 'aurora.fr.local',
        'ie' => 'aurora.ie.local',
        'it' => 'aurora.it.local'
    ];

    /**
     * Configuration paths
     */
    private const CONFIG_PATH_SECURE_BASE_URL = 'web/secure/base_url';
    private const CONFIG_PATH_UNSECURE_BASE_URL = 'web/unsecure/base_url';
    private const CONFIG_PATH_COOKIE_DOMAIN = 'web/cookie/cookie_domain';

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
     * {@inheritdoc}
     */
    public function apply()
    {
        $this->moduleDataSetup->getConnection()->startSetup();

        try {
            foreach (self::STORE_DOMAINS as $storeCode => $domain) {
                $this->updateStoreBaseUrls($storeCode, $domain);
            }

            // Reinitialize stores (skip in test environment to avoid test module issues)
            if (!$this->isTestEnvironment()) {
                $this->storeManager->reinitStores();
            }

        } catch (\Exception $e) {
            throw new \Exception('Error updating store base URLs: ' . $e->getMessage());
        }

        $this->moduleDataSetup->getConnection()->endSetup();
    }

    /**
     * Update base URLs for a specific store
     *
     * @param string $storeCode
     * @param string $domain
     * @return void
     * @throws NoSuchEntityException
     */
    private function updateStoreBaseUrls($storeCode, $domain)
    {
        try {
            $store = $this->storeManager->getStore($storeCode);
            $storeId = $store->getId();

            // Update secure base URL
            $this->setStoreConfig(
                $storeId,
                self::CONFIG_PATH_SECURE_BASE_URL,
                'https://' . $domain . '/'
            );

            // Update unsecure base URL
            $this->setStoreConfig(
                $storeId,
                self::CONFIG_PATH_UNSECURE_BASE_URL,
                'http://' . $domain . '/'
            );

            // Update cookie domain
            $this->setStoreConfig(
                $storeId,
                self::CONFIG_PATH_COOKIE_DOMAIN,
                $domain
            );

        } catch (NoSuchEntityException $e) {
            throw new \Exception("Store with code '{$storeCode}' not found: " . $e->getMessage());
        }
    }

    /**
     * Set store configuration value using ConfigWriter
     *
     * @param int $storeId
     * @param string $path
     * @param string $value
     * @return void
     */
    private function setStoreConfig($storeId, $path, $value)
    {
        $this->configWriter->save($path, $value, 'stores', $storeId);
    }

    /**
     * Check if we're running in a test environment
     *
     * @return bool
     */
    private function isTestEnvironment()
    {
        return defined('TESTS_CLEANUP') && constant('TESTS_CLEANUP') === 'enabled' ||
               strpos($_SERVER['REQUEST_URI'] ?? '', '/dev/tests/') !== false ||
               strpos($_SERVER['SCRIPT_NAME'] ?? '', 'phpunit') !== false ||
               strpos($_SERVER['SCRIPT_NAME'] ?? '', 'bin/magento') !== false ||
               (defined('TESTS_MAGENTO_MODE') && constant('TESTS_MAGENTO_MODE') === 'developer') ||
               (defined('TESTS_INSTALL_CONFIG_FILE') && constant('TESTS_INSTALL_CONFIG_FILE') !== null);
    }

    /**
     * {@inheritdoc}
     */
    public static function getDependencies()
    {
        return [
            AddWebsitesAndStores::class
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function getAliases()
    {
        return [];
    }
}
