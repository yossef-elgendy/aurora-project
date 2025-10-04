<?php
/**
 * Copyright © Esoocter. All rights reserved.
 * See LICENSE.txt for license details.
 */

namespace Esoocter\Migration\Setup\Patch\Data;

use Magento\Framework\Setup\Patch\DataPatchInterface;
use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Catalog\Model\ProductFactory;
use Magento\Catalog\Model\Product;
use Magento\Catalog\Model\Product\Type;
use Magento\Catalog\Model\Product\Attribute\Source\Status;
use Magento\Catalog\Model\Product\Visibility;
use Magento\Catalog\Model\Product\Type\AbstractType;
use Magento\ConfigurableProduct\Model\Product\Type\Configurable;
use Magento\ConfigurableProduct\Api\LinkManagementInterface;
use Magento\ConfigurableProduct\Api\OptionRepositoryInterface;
use Magento\Eav\Api\AttributeRepositoryInterface;
use Magento\Catalog\Api\Data\ProductAttributeInterface;
use Magento\Catalog\Api\CategoryRepositoryInterface;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Framework\App\State;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\File\Csv;
use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\Filesystem;
use Magento\Catalog\Model\ResourceModel\Category\CollectionFactory as CategoryCollectionFactory;
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
     * @var ProductRepositoryInterface
     */
    private $productRepository;

    /**
     * @var ProductFactory
     */
    private $productFactory;

    /**
     * @var CategoryRepositoryInterface
     */
    private $categoryRepository;

    /**
     * @var StoreManagerInterface
     */
    private $storeManager;

    /**
     * @var State
     */
    private $appState;

    /**
     * @var Csv
     */
    private $csvProcessor;

    /**
     * @var Filesystem
     */
    private $filesystem;

    /**
     * @var CategoryCollectionFactory
     */
    private $categoryCollectionFactory;

    /**
     * @var LinkManagementInterface
     */
    private $linkManagement;

    /**
     * @var OptionRepositoryInterface
     */
    private $optionRepository;

    /**
     * @var AttributeRepositoryInterface
     */
    private $attributeRepository;

    /**
     * @var LoggerInterface
     */
    private $logger;



    /**
     * @param ModuleDataSetupInterface $moduleDataSetup
     * @param ProductRepositoryInterface $productRepository
     * @param ProductFactory $productFactory
     * @param CategoryRepositoryInterface $categoryRepository
     * @param StoreManagerInterface $storeManager
     * @param State $appState
     * @param Csv $csvProcessor
     * @param Filesystem $filesystem
     * @param CategoryCollectionFactory $categoryCollectionFactory
     * @param LinkManagementInterface $linkManagement
     * @param OptionRepositoryInterface $optionRepository
     * @param AttributeRepositoryInterface $attributeRepository
     * @param LoggerInterface $logger
     */
    public function __construct(
        ModuleDataSetupInterface $moduleDataSetup,
        ProductRepositoryInterface $productRepository,
        ProductFactory $productFactory,
        CategoryRepositoryInterface $categoryRepository,
        StoreManagerInterface $storeManager,
        State $appState,
        Csv $csvProcessor,
        Filesystem $filesystem,
        CategoryCollectionFactory $categoryCollectionFactory,
        LinkManagementInterface $linkManagement,
        OptionRepositoryInterface $optionRepository,
        AttributeRepositoryInterface $attributeRepository,
        LoggerInterface $logger
    ) {
        $this->moduleDataSetup = $moduleDataSetup;
        $this->productRepository = $productRepository;
        $this->productFactory = $productFactory;
        $this->categoryRepository = $categoryRepository;
        $this->storeManager = $storeManager;
        $this->appState = $appState;
        $this->csvProcessor = $csvProcessor;
        $this->filesystem = $filesystem;
        $this->categoryCollectionFactory = $categoryCollectionFactory;
        $this->linkManagement = $linkManagement;
        $this->optionRepository = $optionRepository;
        $this->attributeRepository = $attributeRepository;
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

            // Import configurable products first
            $this->importConfigurableProducts();

            // Import simple products (including variations)
            $this->importSimpleProducts();

            // Import localized content
            $this->importLocalizedContent();
        } catch (\Exception $e) {
            throw new \Exception('Error importing sportswear catalog: ' . $e->getMessage());
        }

        $this->moduleDataSetup->getConnection()->endSetup();
    }

    /**
     * Find category by name using collection
     */
    private function findCategoryByName($categoryName)
    {
        try {
            $categoryCollection = $this->categoryCollectionFactory->create();
            $categoryCollection->addFieldToFilter('name', $categoryName);
            $categoryCollection->setPageSize(1);

            if ($categoryCollection->getSize() > 0) {
                return $categoryCollection->getFirstItem();
            }
        } catch (\Exception $e) {
            // Category not found, continue
        }

        return null;
    }

    /**
     * Get category ID by name
     */
    private function getCategoryIdByName($categoryName)
    {
        $category = $this->findCategoryByName($categoryName);
        return $category ? $category->getId() : null;
    }

    /**
     * Get all category IDs including parent categories
     */
    private function getCategoryIdsWithParents($categoryName)
    {
        $category = $this->findCategoryByName($categoryName);
        if (!$category) {
            return [];
        }

        $categoryIds = [];

        // Get the category path (includes all parent categories)
        $path = $category->getPath();
        $pathIds = explode('/', $path);

        // Remove root category (ID 1) and default category (ID 2) from path
        $pathIds = array_filter($pathIds, function($id) {
            return $id != '1' && $id != '2';
        });

        // Add all category IDs from the path
        foreach ($pathIds as $categoryId) {
            if ($categoryId && is_numeric($categoryId)) {
                $categoryIds[] = (int)$categoryId;
            }
        }

        return array_unique($categoryIds);
    }

    /**
     * Import simple products and configurable variations from CSV
     */
    private function importSimpleProducts()
    {
        // Import simple products
        $csvFile = $this->getCsvFilePath('simple_products.csv');
        $data = $this->csvProcessor->getData($csvFile);
        $headers = array_shift($data);

        foreach ($data as $row) {
            // Ensure row has same number of elements as headers
            if (count($row) === count($headers)) {
                $productData = array_combine($headers, $row);
                $this->createSimpleProduct($productData);
            } else {
                // Log the mismatch and skip this row
                $this->logger->warning("CSV row mismatch in simple_products.csv. Headers: " . count($headers) . ", Row: " . count($row));
            }
        }

        // Import configurable variations
        $variationsFile = $this->getCsvFilePath('configurable_variations.csv');
        $variationsData = $this->csvProcessor->getData($variationsFile);
        $variationsHeaders = array_shift($variationsData);

        foreach ($variationsData as $row) {
            // Ensure row has same number of elements as headers
            if (count($row) === count($variationsHeaders)) {
                $variationData = array_combine($variationsHeaders, $row);
                $this->createConfigurableVariation($variationData);
            } else {
                // Log the mismatch and skip this row
                $this->logger->warning("CSV row mismatch in configurable_variations.csv. Headers: " . count($variationsHeaders) . ", Row: " . count($row));
            }
        }
    }

    /**
     * Import configurable products only
     */
    private function importConfigurableProducts()
    {
        // Import configurable products
        $csvFile = $this->getCsvFilePath('configurable_products.csv');
        $data = $this->csvProcessor->getData($csvFile);
        $headers = array_shift($data);

        foreach ($data as $row) {
            // Ensure row has same number of elements as headers
            if (count($row) === count($headers)) {
                $productData = array_combine($headers, $row);
                $this->createConfigurableProduct($productData);
            } else {
                // Log the mismatch and skip this row
                $this->logger->warning("CSV row mismatch in configurable_products.csv. Headers: " . count($headers) . ", Row: " . count($row));
            }
        }
    }

    /**
     * Import localized content
     */
    private function importLocalizedContent()
    {
        $stores = ['fr', 'it', 'ie'];

        foreach ($stores as $storeCode) {
            $csvFile = $this->getCsvFilePath("localized_products_{$storeCode}.csv");
            if (file_exists($csvFile)) {
                $data = $this->csvProcessor->getData($csvFile);
                $headers = array_shift($data);

                foreach ($data as $row) {
                    // Ensure row has same number of elements as headers
                    if (count($row) === count($headers)) {
                        $localizedData = array_combine($headers, $row);
                        $this->updateProductLocalization($localizedData, $storeCode);
                    } else {
                        // Log the mismatch and skip this row
                        $this->logger->warning("CSV row mismatch in localized_products_{$storeCode}.csv. Headers: " . count($headers) . ", Row: " . count($row));
                    }
                }
            }
        }
    }

    /**
     * Create simple product
     */
    private function createSimpleProduct($data)
    {
        try {
            $product = $this->productFactory->create();
            $product->setSku($data['sku']);
            $product->setName($data['name']);
            $product->setTypeId(Type::TYPE_SIMPLE);
            $product->setAttributeSetId(4); // Default attribute set
            $product->setPrice($data['price']);
            $product->setWeight($data['weight']);
            $product->setStatus(Status::STATUS_ENABLED);
            $product->setVisibility(Visibility::VISIBILITY_BOTH);
            $product->setDescription($data['description']);
            $product->setShortDescription($data['short_description']);
            $product->setMetaTitle($data['meta_title']);
            $product->setMetaDescription($data['meta_description']);
            $product->setMetaKeyword($data['meta_keywords']);
            $product->setUrlKey($data['url_key']);
            $product->setStockData([
                'manage_stock' => $data['manage_stock'],
                'use_config_manage_stock' => $data['use_config_manage_stock'],
                'qty' => $data['qty'],
                'is_in_stock' => $data['is_in_stock'],
                'stock_status' => $data['stock_status']
            ]);

            // Set categories (including parent categories)
            if (isset($data['categories']) && !empty($data['categories'])) {
                $categoryIds = $this->getCategoryIdsWithParents($data['categories']);
                if (!empty($categoryIds)) {
                    $product->setCategoryIds($categoryIds);
                }
            }

            // Set websites (all available websites)
            $websiteIds = $this->getAllWebsiteIds();
            $product->setWebsiteIds($websiteIds);

            // Set custom attributes
            $this->setProductAttributes($product, $data);

            $this->productRepository->save($product);

        } catch (\Exception $e) {
            throw new \Exception("Error creating simple product {$data['sku']}: " . $e->getMessage());
        }
    }

    /**
     * Create configurable product
     */
    private function createConfigurableProduct($data)
    {
        try {
            $product = $this->productFactory->create();
            $product->setSku($data['sku']);
            $product->setName($data['name']);
            $product->setTypeId(Configurable::TYPE_CODE);
            $product->setAttributeSetId(4);
            $product->setPrice($data['price']);
            $product->setWeight($data['weight']);
            $product->setStatus(Status::STATUS_ENABLED);
            $product->setVisibility(Visibility::VISIBILITY_BOTH);
            $product->setDescription($data['description']);
            $product->setShortDescription($data['short_description']);
            $product->setMetaTitle($data['meta_title']);
            $product->setMetaDescription($data['meta_description']);
            $product->setMetaKeyword($data['meta_keywords']);
            $product->setUrlKey($data['url_key']);
            $product->setStockData([
                'manage_stock' => $data['manage_stock'],
                'use_config_manage_stock' => $data['use_config_manage_stock']
            ]);

            // Set categories (including parent categories)
            if (isset($data['categories']) && !empty($data['categories'])) {
                $categoryIds = $this->getCategoryIdsWithParents($data['categories']);
                if (!empty($categoryIds)) {
                    $product->setCategoryIds($categoryIds);
                }
            }

            // Set websites (all available websites)
            $websiteIds = $this->getAllWebsiteIds();
            $product->setWebsiteIds($websiteIds);

            // Set custom attributes
            $this->setProductAttributes($product, $data);

            $this->productRepository->save($product);

        } catch (\Exception $e) {
            throw new \Exception("Error creating configurable product {$data['sku']}: " . $e->getMessage());
        }
    }

    /**
     * Create configurable variation
     */
    private function createConfigurableVariation($data)
    {
        try {
            $product = $this->productFactory->create();
            $product->setSku($data['sku']);
            $product->setName($data['name']);
            $product->setTypeId(Type::TYPE_SIMPLE);
            $product->setAttributeSetId(4);
            $product->setPrice($data['price']);
            $product->setWeight($data['weight']);
            $product->setStatus(Status::STATUS_ENABLED);
            $product->setVisibility(Visibility::VISIBILITY_NOT_VISIBLE);
            $product->setDescription($data['description']);
            $product->setShortDescription($data['short_description']);
            $product->setMetaTitle($data['meta_title']);
            $product->setMetaDescription($data['meta_description']);
            $product->setMetaKeyword($data['meta_keywords']);
            $product->setUrlKey($data['url_key']);
            $product->setStockData([
                'manage_stock' => $data['manage_stock'],
                'use_config_manage_stock' => $data['use_config_manage_stock'],
                'qty' => $data['qty'],
                'is_in_stock' => $data['is_in_stock'],
                'stock_status' => $data['stock_status']
            ]);

            // Set categories (including parent categories)
            if (isset($data['categories']) && !empty($data['categories'])) {
                $categoryIds = $this->getCategoryIdsWithParents($data['categories']);
                if (!empty($categoryIds)) {
                    $product->setCategoryIds($categoryIds);
                }
            }

            // Set websites (all available websites)
            $websiteIds = $this->getAllWebsiteIds();
            $product->setWebsiteIds($websiteIds);

            // Set custom attributes
            $this->setProductAttributes($product, $data);

            $this->productRepository->save($product);

            // If parent_sku is provided, assign this variation to the configurable product
            if (isset($data['parent_sku']) && !empty($data['parent_sku'])) {
                $this->assignVariationToConfigurable($data['parent_sku'], $data['sku']);
            }

        } catch (\Exception $e) {
            throw new \Exception("Error creating configurable variation {$data['sku']}: " . $e->getMessage());
        }
    }

    /**
     * Assign variation to configurable product
     */
    private function assignVariationToConfigurable($parentSku, $variationSku)
    {
        try {
            // Get the configurable product
            $configurableProduct = $this->productRepository->get($parentSku);
            
            if ($configurableProduct->getTypeId() === Configurable::TYPE_CODE) {
                // Link the variation to the configurable product
                $this->linkManagement->addChild($parentSku, $variationSku);
            }
        } catch (\Exception $e) {
            // Log error but don't stop the process
            // The configurable product might not exist yet or there might be other issues
        }
    }

    /**
     * Update product localization
     */
    private function updateProductLocalization($data, $storeCode)
    {
        try {
            $store = $this->storeManager->getStore($storeCode);

            /** @var Product $product */
            $product = $this->productRepository->get($data['sku'], false, $store->getId());

            $product->setName($data['name']);
            $product->setData('description', $data['description']);
            $product->setData('short_description', $data['short_description']);
            $product->setData('meta_title', $data['meta_title']);
            $product->setData('meta_description', $data['meta_description']);
            $product->setData('meta_keyword', $data['meta_keywords']);

            // Set categories (including parent categories) for localized content
            if (isset($data['categories']) && !empty($data['categories'])) {
                $categoryIds = $this->getCategoryIdsWithParents($data['categories']);
                if (!empty($categoryIds)) {
                    $product->setCategoryIds($categoryIds);
                }
            }

            // Ensure product is assigned to all websites
            $websiteIds = $this->getAllWebsiteIds();
            $product->setWebsiteIds($websiteIds);

            $this->productRepository->save($product);
        } catch (\Exception $e) {
            throw new \Exception("Error updating localization for product {$data['sku']} in store {$storeCode}: " . $e->getMessage());
        }
    }

    /**
     * Get all website IDs using StoreManager
     */
    private function getAllWebsiteIds()
    {
        $websiteIds = [];

        try {
            $websites = $this->storeManager->getWebsites();

            foreach ($websites as $website) {
                $websiteIds[] = $website->getId();
            }
        } catch (\Exception $e) {
            // Fallback to default website if StoreManager fails
            $websiteIds = [1];
            $this->logger->warning("Failed to get websites from StoreManager, using default website: " . $e->getMessage());
        }

        return $websiteIds;
    }

    /**
     * Get CSV file path
     */
    private function getCsvFilePath($filename)
    {
        $moduleDir = $this->filesystem->getDirectoryRead(DirectoryList::APP);
        return $moduleDir->getAbsolutePath() . 'code/Esoocter/Migration/Setup/Patch/Data/csv/' . $filename;
    }

    /**
     * Set product attributes
     */
    private function setProductAttributes($product, $data)
    {
        // Set size attribute if available
        if (isset($data['size']) && !empty($data['size'])) {
            $product->setCustomAttribute('size', $data['size']);
        }

        // Set color attribute if available
        if (isset($data['color']) && !empty($data['color'])) {
            $product->setCustomAttribute('color', $data['color']);
        }

        // Set sport type attribute if available
        if (isset($data['sport_type']) && !empty($data['sport_type'])) {
            $product->setCustomAttribute('sport_type', $data['sport_type']);
        }

        // Set material attribute if available
        if (isset($data['material']) && !empty($data['material'])) {
            $product->setCustomAttribute('material', $data['material']);
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
