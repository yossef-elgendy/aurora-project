<?php
/**
 * Copyright © Escooter. All rights reserved.
 * See LICENSE.txt for license details.
 */

namespace Escooter\Migration\Setup\Patch\Data;

use Magento\Framework\Setup\Patch\DataPatchInterface;
use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Framework\File\Csv;
use Magento\Framework\Filesystem;
use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\App\State;
use Magento\Directory\Model\CurrencyFactory;
use Magento\Store\Model\Store;
use Magento\Framework\App\Config\Storage\WriterInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;

/**
 * Data patch to setup currency-specific pricing for products
 */
class SetupCurrencyPricing implements DataPatchInterface
{
    /**
     * @var ModuleDataSetupInterface
     */
    private $moduleDataSetup;

    /**
     * @var ProductRepositoryInterface
     */
    private $productRepository;

    /**
     * @var StoreManagerInterface
     */
    private $storeManager;

    /**
     * @var Csv
     */
    private $csvProcessor;

    /**
     * @var Filesystem
     */
    private $filesystem;

    /**
     * @var State
     */
    private $appState;

    /**
     * @var CurrencyFactory
     */
    private $currencyFactory;

    /**
     * @var WriterInterface
     */
    private $configWriter;

    /**
     * Currency conversion rates (USD to other currencies)
     */
    private const CURRENCY_RATES = [
        'USD' => 1.0,
        'GBP' => 0.8,  // 1 USD = 0.8 GBP
        'EUR' => 0.9   // 1 USD = 0.9 EUR
    ];

    /**
     * @param ModuleDataSetupInterface $moduleDataSetup
     * @param ProductRepositoryInterface $productRepository
     * @param StoreManagerInterface $storeManager
     * @param Csv $csvProcessor
     * @param Filesystem $filesystem
     * @param State $appState
     * @param CurrencyFactory $currencyFactory
     * @param WriterInterface $configWriter
     */
    public function __construct(
        ModuleDataSetupInterface $moduleDataSetup,
        ProductRepositoryInterface $productRepository,
        StoreManagerInterface $storeManager,
        Csv $csvProcessor,
        Filesystem $filesystem,
        State $appState,
        CurrencyFactory $currencyFactory,
        WriterInterface $configWriter
    ) {
        $this->moduleDataSetup = $moduleDataSetup;
        $this->productRepository = $productRepository;
        $this->storeManager = $storeManager;
        $this->csvProcessor = $csvProcessor;
        $this->filesystem = $filesystem;
        $this->appState = $appState;
        $this->currencyFactory = $currencyFactory;
        $this->configWriter = $configWriter;
    }

    /**
     * {@inheritdoc}
     */
    public function apply()
    {
        $this->moduleDataSetup->getConnection()->startSetup();

        try {
            try {
                $this->appState->setAreaCode('adminhtml');
            } catch (\Exception $e) {
                // Area code is already set, continue
            }

            // Configure catalog price scope to Website
            $this->configureCatalogPriceScope();

            // Setup currency rates for automatic conversion
            $this->setupCurrencyRates();

        } catch (\Exception $e) {
            throw new \Exception('Error setting up currency pricing: ' . $e->getMessage());
        }

        $this->moduleDataSetup->getConnection()->endSetup();
    }

    /**
     * Configure catalog price scope to Website level
     */
    private function configureCatalogPriceScope()
    {
        try {
            // Set catalog price scope to Website (1 = Website, 0 = Store View)
            $this->configWriter->save(
                'catalog/price/scope',
                1,
                ScopeConfigInterface::SCOPE_TYPE_DEFAULT,
                0
            );
        } catch (\Exception $e) {
            throw new \Exception('Error configuring catalog price scope: ' . $e->getMessage());
        }
    }


    /**
     * Setup currency rates for conversion
     */
    private function setupCurrencyRates()
    {
        $connection = $this->moduleDataSetup->getConnection();
        $table = $this->moduleDataSetup->getTable('directory_currency_rate');

        // Clear existing rates for all currencies we're setting up
        $connection->delete($table, [
            'currency_from IN (?)' => ['USD', 'GBP', 'EUR'],
            'currency_to IN (?)' => ['USD', 'GBP', 'EUR']
        ]);

        // Insert new rates (USD as base currency)
        $rates = [
            [
                'currency_from' => 'USD',
                'currency_to' => 'GBP',
                'rate' => 0.8
            ],
            [
                'currency_from' => 'USD',
                'currency_to' => 'EUR',
                'rate' => 0.9
            ],
            [
                'currency_from' => 'GBP',
                'currency_to' => 'USD',
                'rate' => 1.25
            ],
            [
                'currency_from' => 'EUR',
                'currency_to' => 'USD',
                'rate' => 1.11
            ]
        ];

        foreach ($rates as $rate) {
            $connection->insert($table, $rate);
        }
    }

    /**
     * Get CSV file path
     */
    private function getCsvFilePath($filename)
    {
        $moduleDir = $this->filesystem->getDirectoryRead(DirectoryList::APP);
        return $moduleDir->getAbsolutePath() . 'code/Escooter/Migration/Setup/Patch/Data/csv/' . $filename;
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
