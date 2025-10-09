<?php
/**
 * Copyright © Escooter. All rights reserved.
 * See LICENSE.txt for license details.
 */

namespace Escooter\Migration\Setup\Patch\Data;

use Magento\Framework\Setup\Patch\DataPatchInterface;
use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\Framework\App\State;
use Magento\Store\Model\StoreManagerInterface;
use Escooter\Migration\Helper\ProductImporterHelper;
use Escooter\Migration\Helper\ConfigurableProductImporterHelper;
use Escooter\Migration\Helper\CsvImporterHelper;
use Psr\Log\LoggerInterface;

/**
 * Data patch to import sportswear catalog from CSV files
 */
class ImportSportswearCatalog implements DataPatchInterface
{
    /**
     * @var ModuleDataSetupInterface
     */
    private $moduleDataSetup;

    /**
     * @var State
     */
    private $appState;

    /**
     * @var StoreManagerInterface
     */
    private $storeManager;

    /**
     * @var ProductImporterHelper
     */
    private $productImporterHelper;

    /**
     * @var ConfigurableProductImporterHelper
     */
    private $configurableProductImporterHelper;

    /**
     * @var CsvImporterHelper
     */
    private $csvImporterHelper;

    /**
     * @var LoggerInterface
     */
    private $logger;


    /**
     * @param ModuleDataSetupInterface $moduleDataSetup
     * @param State $appState
     * @param StoreManagerInterface $storeManager
     * @param ProductImporterHelper $productImporterHelper
     * @param ConfigurableProductImporterHelper $configurableProductImporterHelper
     * @param CsvImporterHelper $csvImporterHelper
     * @param LoggerInterface $logger
     */
    public function __construct(
        ModuleDataSetupInterface $moduleDataSetup,
        State $appState,
        StoreManagerInterface $storeManager,
        ProductImporterHelper $productImporterHelper,
        ConfigurableProductImporterHelper $configurableProductImporterHelper,
        CsvImporterHelper $csvImporterHelper,
        LoggerInterface $logger
    ) {
        $this->moduleDataSetup = $moduleDataSetup;
        $this->appState = $appState;
        $this->storeManager = $storeManager;
        $this->productImporterHelper = $productImporterHelper;
        $this->configurableProductImporterHelper = $configurableProductImporterHelper;
        $this->csvImporterHelper = $csvImporterHelper;
        $this->logger = $logger;
    }

    /**
     * {@inheritdoc}
     */
    public function apply()
    {
        $this->moduleDataSetup->getConnection()->startSetup();

        try {
            // Set area code to avoid issues (only if not already set)
            try {
                $this->appState->setAreaCode('adminhtml');
            } catch (\Exception $e) {
                // Area code is already set, continue
            }

            // Step 1: Import simple products first (including variations)
            $this->logger->info('Starting simple products import...');
            $this->importSimpleProducts();
            $this->logger->info('Simple products import completed.');

            // Step 2: Import configurable products
            $this->logger->info('Starting configurable products import...');
            $this->importConfigurableProducts();
            $this->logger->info('Configurable products import completed.');

            // Step 3: Associate simple products with configurable products
            $this->logger->info('Starting configurable product associations...');
            $this->associateConfigurableProducts();
            $this->logger->info('Configurable product associations completed.');

            // Step 4: Import localized content
            $this->logger->info('Starting localized content import...');
            $this->importLocalizedContent();
            $this->logger->info('Localized content import completed.');
        } catch (\Exception $e) {
            $this->logger->error('Error importing sportswear catalog: ' . $e->getMessage());
            throw new \Exception('Error importing sportswear catalog: ' . $e->getMessage());
        }

        $this->moduleDataSetup->getConnection()->endSetup();
    }


    /**
     * Import simple products from CSV
     */
    private function importSimpleProducts()
    {
        // Import simple products
        $simpleProducts = $this->csvImporterHelper->importCsvData('simple_products.csv');
        foreach ($simpleProducts as $productData) {
            $this->productImporterHelper->createSimpleProduct($productData);
        }

        // Import configurable variations (simple products that are variations)
        $variations = $this->csvImporterHelper->importCsvData('configurable_variations.csv');
        foreach ($variations as $variationData) {
            $this->configurableProductImporterHelper->createConfigurableVariation($variationData);
        }
    }

    /**
     * Import configurable products only
     */
    private function importConfigurableProducts()
    {
        $configurableProducts = $this->csvImporterHelper->importCsvData('configurable_products.csv');
        foreach ($configurableProducts as $productData) {
            $this->configurableProductImporterHelper->createConfigurableProduct($productData);
        }
    }

    /**
     * Import localized content
     */
    private function importLocalizedContent()
    {
        $stores = ['fr', 'it', 'ie'];

        foreach ($stores as $storeCode) {
            try {
                $localizedData = $this->csvImporterHelper->importCsvData("localized_products_{$storeCode}.csv");
                foreach ($localizedData as $data) {
                    $this->updateProductLocalization($data, $storeCode);
                }
            } catch (\Exception $e) {
                $this->logger->info("Localized content file not found for store: {$storeCode}");
                continue;
            }
        }
    }




    /**
     * Update product localization
     */
    private function updateProductLocalization($data, $storeCode)
    {
        // This method can be implemented later if needed for localized content
        $this->logger->info("Localized content update for {$data['sku']} in store {$storeCode} - to be implemented");
    }


    /**
     * Associate simple products with configurable products
     */
    private function associateConfigurableProducts()
    {
        $variations = $this->csvImporterHelper->importCsvData('configurable_variations.csv');
        $variationsByParent = $this->csvImporterHelper->groupVariationsByParent($variations);

        // Associate variations with configurable products
        foreach ($variationsByParent as $parentSku => $variationSkus) {
            $this->configurableProductImporterHelper->associateVariationsToConfigurable($parentSku, $variationSkus);
        }
    }


    /**
     * {@inheritdoc}
     */
    public static function getDependencies()
    {
        return [
            CreateSportswearCategories::class,
            SetupProductAttributes::class,
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
