<?php
/**
 * Copyright © Escooter. All rights reserved.
 * See LICENSE.txt for license details.
 */

namespace Escooter\Migration\Helper;

use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Catalog\Model\ProductFactory;
use Magento\Catalog\Model\Product;
use Magento\Catalog\Model\Product\Type;
use Magento\Catalog\Model\Product\Attribute\Source\Status;
use Magento\Catalog\Model\Product\Visibility;
use Magento\ConfigurableProduct\Model\Product\Type\Configurable;
use Magento\ConfigurableProduct\Api\LinkManagementInterface;
use Magento\ConfigurableProduct\Helper\Product\Options\Factory as ConfigurableOptionsFactory;
use Magento\Eav\Model\Config as EavConfig;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Catalog\Model\ResourceModel\Category\CollectionFactory as CategoryCollectionFactory;
use Magento\Catalog\Api\Data\ProductExtensionFactory;
use Magento\CatalogInventory\Api\StockRegistryInterface;
use Psr\Log\LoggerInterface;

/**
 * Helper class for importing configurable products
 */
class ConfigurableProductImporterHelper
{
    /**
     * @var ProductRepositoryInterface
     */
    private $productRepository;

    /**
     * @var ProductFactory
     */
    private $productFactory;

    /**
     * @var LinkManagementInterface
     */
    private $linkManagement;

    /**
     * @var EavConfig
     */
    private $eavConfig;

    /**
     * @var StoreManagerInterface
     */
    private $storeManager;

    /**
     * @var CategoryCollectionFactory
     */
    private $categoryCollectionFactory;

    /**
     * @var ConfigurableOptionsFactory
     */
    private $configurableOptionsFactory;

    /**
     * @var ProductExtensionFactory
     */
    private $productExtensionFactory;

    /**
     * @var StockRegistryInterface
     */
    private $stockRegistry;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @param ProductRepositoryInterface $productRepository
     * @param ProductFactory $productFactory
     * @param LinkManagementInterface $linkManagement
     * @param EavConfig $eavConfig
     * @param StoreManagerInterface $storeManager
     * @param CategoryCollectionFactory $categoryCollectionFactory
     * @param ConfigurableOptionsFactory $configurableOptionsFactory
     * @param ProductExtensionFactory $productExtensionFactory
     * @param StockRegistryInterface $stockRegistry
     * @param LoggerInterface $logger
     */
    public function __construct(
        ProductRepositoryInterface $productRepository,
        ProductFactory $productFactory,
        LinkManagementInterface $linkManagement,
        EavConfig $eavConfig,
        StoreManagerInterface $storeManager,
        CategoryCollectionFactory $categoryCollectionFactory,
        ConfigurableOptionsFactory $configurableOptionsFactory,
        ProductExtensionFactory $productExtensionFactory,
        StockRegistryInterface $stockRegistry,
        LoggerInterface $logger
    ) {
        $this->productRepository = $productRepository;
        $this->productFactory = $productFactory;
        $this->linkManagement = $linkManagement;
        $this->eavConfig = $eavConfig;
        $this->storeManager = $storeManager;
        $this->categoryCollectionFactory = $categoryCollectionFactory;
        $this->configurableOptionsFactory = $configurableOptionsFactory;
        $this->productExtensionFactory = $productExtensionFactory;
        $this->stockRegistry = $stockRegistry;
        $this->logger = $logger;
    }

    /**
     * Create configurable product
     *
     * @param array $data
     * @return Product
     * @throws \Exception
     */
    public function createConfigurableProduct(array $data): Product
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

            // Set categories
            if (isset($data['categories']) && !empty($data['categories']) && is_string($data['categories'])) {
                $categoryIds = $this->getCategoryIdsWithParents($data['categories']);
                if (!empty($categoryIds)) {
                    $product->setCategoryIds($categoryIds);
                }
            }

            // Set websites
            $websiteIds = $this->getAllWebsiteIds();
            $product->setWebsiteIds($websiteIds);

            // Set custom attributes
            $this->setProductAttributes($product, $data);

            // Save the product (configurable options will be set during association)
            $this->productRepository->save($product);
            
            $this->logger->info("Created configurable product: {$data['sku']}");

            return $product;

        } catch (\Exception $e) {
            $this->logger->error("Error creating configurable product {$data['sku']}: " . $e->getMessage());
            throw new \Exception("Error creating configurable product {$data['sku']}: " . $e->getMessage());
        }
    }

    /**
     * Create configurable variation (simple product that is a variation)
     *
     * @param array $data
     * @return Product
     * @throws \Exception
     */
    public function createConfigurableVariation(array $data): Product
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
            $product->setVisibility(Visibility::VISIBILITY_NOT_VISIBLE); // Simple products typically not visible individually
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

            // Set categories
            if (isset($data['categories']) && !empty($data['categories']) && is_string($data['categories'])) {
                $categoryIds = $this->getCategoryIdsWithParents($data['categories']);
                if (!empty($categoryIds)) {
                    $product->setCategoryIds($categoryIds);
                }
            }

            // Set websites
            $websiteIds = $this->getAllWebsiteIds();
            $product->setWebsiteIds($websiteIds);

            // Set custom attributes
            $this->setProductAttributes($product, $data);

            $this->productRepository->save($product);
            $this->logger->info("Created configurable variation: {$data['sku']}");

            return $product;

        } catch (\Exception $e) {
            $this->logger->error("Error creating configurable variation {$data['sku']}: " . $e->getMessage());
            throw new \Exception("Error creating configurable variation {$data['sku']}: " . $e->getMessage());
        }
    }

    /**
     * Associate variations to configurable product
     *
     * @param string $parentSku
     * @param array $variationSkus
     * @return void
     */
    public function associateVariationsToConfigurable(string $parentSku, array $variationSkus): void
    {
        try {
            // Get the configurable product
            $configurableProduct = $this->productRepository->get($parentSku);
            
            if (!$configurableProduct || $configurableProduct->getTypeId() !== Configurable::TYPE_CODE) {
                throw new \Exception("Configurable product {$parentSku} not found or not configurable type");
            }

            // Load all variation products
            $variationProducts = [];
            foreach ($variationSkus as $variationSku) {
                try {
                    $variationProducts[] = $this->productRepository->get($variationSku);
                } catch (\Exception $e) {
                    $this->logger->warning("Could not load variation product {$variationSku}: " . $e->getMessage());
                }
            }

            if (empty($variationProducts)) {
                throw new \Exception("No valid variation products found for {$parentSku}");
            }

            // Detect which attributes vary among the children
            $configurableAttributeCodes = $this->detectConfigurableAttributes($variationProducts);

            if (empty($configurableAttributeCodes)) {
                throw new \Exception("No configurable attributes detected for {$parentSku}");
            }

            $this->logger->info("Detected configurable attributes for {$parentSku}: " . implode(', ', $configurableAttributeCodes));

            // Step 1: Create and set configurable product options
            $configurableOptions = $this->buildConfigurableOptions($configurableAttributeCodes, $variationProducts);

            // Step 2: Link child product IDs
            $childProductIds = [];
            foreach ($variationProducts as $variationProduct) {
                $childProductIds[] = $variationProduct->getId();
            }

            // Step 3: Set extension attributes
            $extensionAttributes = $configurableProduct->getExtensionAttributes();
            if ($extensionAttributes === null) {
                $extensionAttributes = $this->productExtensionFactory->create();
            }

            $extensionAttributes->setConfigurableProductOptions($configurableOptions);
            $extensionAttributes->setConfigurableProductLinks($childProductIds);
            $configurableProduct->setExtensionAttributes($extensionAttributes);

            $this->logger->info("Attempting to save configurable product {$parentSku} with " . count($configurableOptions) . " options and " . count($childProductIds) . " child products");

            // Save the configurable product
            try {
                $this->productRepository->save($configurableProduct);
                $this->logger->info("Successfully saved configurable product {$parentSku}");
            } catch (\Exception $e) {
                $this->logger->error("Failed to save configurable product {$parentSku}: " . $e->getMessage());
                $this->logger->error("Stack trace: " . $e->getTraceAsString());
                throw $e;
            }

            // Step 4: Set stock for configurable product
            $this->setConfigurableStock($parentSku);

            $this->logger->info("Successfully associated " . count($variationProducts) . " variations with configurable product: {$parentSku}");
        } catch (\Exception $e) {
            $this->logger->error("Error associating variations to {$parentSku}: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Set product attributes
     *
     * @param Product $product
     * @param array $data
     * @return void
     */
    private function setProductAttributes(Product $product, array $data): void
    {
        // Set size attribute if available
        if (isset($data['size']) && !empty($data['size'])) {
            $optionId = $this->getAttributeOptionId('size', $data['size']);
            if ($optionId) {
                $product->setCustomAttribute('size', $optionId);
            }
        }

        // Set color attribute if available
        if (isset($data['color']) && !empty($data['color'])) {
            $optionId = $this->getAttributeOptionId('color', $data['color']);
            if ($optionId) {
                $product->setCustomAttribute('color', $optionId);
            }
        }

        // Set sport type attribute if available
        if (isset($data['sport_type']) && !empty($data['sport_type'])) {
            $optionId = $this->getAttributeOptionId('sport_type', $data['sport_type']);
            if ($optionId) {
                $product->setCustomAttribute('sport_type', $optionId);
            }
        }

        // Set material attribute if available
        if (isset($data['material']) && !empty($data['material'])) {
            $optionId = $this->getAttributeOptionId('material', $data['material']);
            if ($optionId) {
                $product->setCustomAttribute('material', $optionId);
            }
        }
    }

    /**
     * Get attribute option ID by attribute code and option value
     *
     * @param string $attributeCode
     * @param string $optionValue
     * @return int|null
     */
    private function getAttributeOptionId(string $attributeCode, string $optionValue): ?int
    {
        try {
            $attribute = $this->eavConfig->getAttribute(Product::ENTITY, $attributeCode);
            if (!$attribute->getId()) {
                $this->logger->warning("Attribute {$attributeCode} not found");
                return null;
            }

            // Get attribute options
            $options = $attribute->getSource()->getAllOptions();

            foreach ($options as $option) {
                if ($option['label'] === $optionValue && $option['value']) {
                    return (int)$option['value'];
                }
            }

            $this->logger->warning("Option '{$optionValue}' not found for attribute '{$attributeCode}'");
            return null;
        } catch (\Exception $e) {
            $this->logger->error("Error getting option ID for {$attributeCode}: {$optionValue} - " . $e->getMessage());
            return null;
        }
    }

    /**
     * Detect configurable attributes from variation products
     * Returns valid configurable attributes that vary among children
     *
     * @param array $variationProducts
     * @return array
     */
    private function detectConfigurableAttributes(array $variationProducts): array
    {
        // Only use the 4 attributes we created through our data patch
        $configurableAttributeCodes = ['size', 'color', 'sport_type', 'material'];
        $detectedAttributes = [];
        
        // Check each attribute to see if it varies and is valid
        foreach ($configurableAttributeCodes as $attributeCode) {
            $values = [];
            foreach ($variationProducts as $product) {
                $value = $product->getData($attributeCode);
                if ($value) {
                    $values[] = $value;
                }
            }

            // If this attribute has different values across variations, it could be configurable
            $uniqueValues = array_unique($values);
            if (count($uniqueValues) > 1) {
                // Validate that the attribute can be used for configurable products
                try {
                    $attribute = $this->eavConfig->getAttribute(Product::ENTITY, $attributeCode);
                    if ($attribute->getId() && 
                        $attribute->getIsVisible() && 
                        $attribute->usesSource() &&
                        $attribute->getIsGlobal() == \Magento\Eav\Model\Entity\Attribute\ScopedAttributeInterface::SCOPE_GLOBAL) {
                        
                        $this->logger->info("Detected varying attribute '{$attributeCode}' with " . count($uniqueValues) . " unique values");
                        $detectedAttributes[] = $attributeCode;
                    } else {
                        $this->logger->warning("Attribute '{$attributeCode}' varies but doesn't meet configurable requirements, skipping");
                    }
                } catch (\Exception $e) {
                    $this->logger->error("Error validating attribute '{$attributeCode}': " . $e->getMessage());
                }
            }
        }

        if (!empty($detectedAttributes)) {
            $this->logger->info("Using " . count($detectedAttributes) . " configurable attribute(s): " . implode(', ', $detectedAttributes));
        }

        return $detectedAttributes;
    }

    /**
     * Build configurable product options
     *
     * @param array $attributeCodes
     * @param array $variationProducts
     * @return array
     */
    private function buildConfigurableOptions(array $attributeCodes, array $variationProducts): array
    {
        $configurableAttributesData = [];
        $position = 0;

        foreach ($attributeCodes as $attributeCode) {
            try {
                $attribute = $this->eavConfig->getAttribute(Product::ENTITY, $attributeCode);
                if (!$attribute->getId()) {
                    $this->logger->error("Attribute {$attributeCode} not found");
                    continue;
                }

                // Log attribute properties for debugging
                $this->logger->info("Building options for attribute {$attributeCode}: is_global=" . $attribute->getIsGlobal() . 
                    ", is_visible=" . $attribute->getIsVisible() . 
                    ", uses_source=" . ($attribute->usesSource() ? '1' : '0'));

                // Collect all unique option values for this attribute from variations
                $attributeValues = [];
                
                foreach ($variationProducts as $variationProduct) {
                    $optionId = $variationProduct->getData($attributeCode);
                    if ($optionId && !isset($attributeValues[$optionId])) {
                        $attributeValues[$optionId] = [
                            'label' => $this->getOptionLabel($attribute, $optionId),
                            'attribute_id' => $attribute->getId(),
                            'value_index' => $optionId,
                        ];
                    }
                }

                if (empty($attributeValues)) {
                    continue;
                }

                // Build configurable attribute data
                $configurableAttributesData[] = [
                    'attribute_id' => $attribute->getId(),
                    'code' => $attribute->getAttributeCode(),
                    'label' => $attribute->getStoreLabel(),
                    'position' => $position++,
                    'values' => array_values($attributeValues),
                ];

                $this->logger->info("Built configurable option for attribute: {$attributeCode} with " . count($attributeValues) . " values");
            } catch (\Exception $e) {
                $this->logger->error("Error building configurable option for {$attributeCode}: " . $e->getMessage());
            }
        }

        // Use the Factory to create configurable options from the data
        return $this->configurableOptionsFactory->create($configurableAttributesData);
    }

    /**
     * Get option label by attribute and option ID
     *
     * @param \Magento\Eav\Model\Entity\Attribute $attribute
     * @param int $optionId
     * @return string
     */
    private function getOptionLabel($attribute, $optionId): string
    {
        try {
            $options = $attribute->getSource()->getAllOptions();
            foreach ($options as $option) {
                if ($option['value'] == $optionId) {
                    return $option['label'];
                }
            }
        } catch (\Exception $e) {
            $this->logger->warning("Could not get option label for attribute {$attribute->getAttributeCode()}: " . $e->getMessage());
        }
        return (string)$optionId;
    }

    /**
     * Set stock for configurable product
     *
     * @param string $sku
     * @return void
     */
    private function setConfigurableStock(string $sku): void
    {
        try {
            $stockItem = $this->stockRegistry->getStockItemBySku($sku);
            $stockItem->setIsInStock(true);
            $stockItem->setQty(1);
            $stockItem->setManageStock(false);
            $this->stockRegistry->updateStockItemBySku($sku, $stockItem);

            $this->logger->info("Set stock for configurable product: {$sku}");
        } catch (\Exception $e) {
            $this->logger->warning("Could not set stock for configurable product {$sku}: " . $e->getMessage());
        }
    }

    /**
     * Get all category IDs including parent categories
     *
     * @param string $categoryName
     * @return array
     */
    private function getCategoryIdsWithParents(string $categoryName): array
    {
        $category = $this->findCategoryByName($categoryName);
        if (!$category) {
            return [];
        }

        $categoryIds = [];

        // Get the category path (includes all parent categories)
        $path = $category->getPath();
        $pathIds = empty($path) ? [] : explode('/', $path);

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
     * Find category by name using collection
     *
     * @param string $categoryName
     * @return \Magento\Catalog\Model\Category|null
     */
    private function findCategoryByName(string $categoryName)
    {
        try {
            $categoryCollection = $this->categoryCollectionFactory->create();
            $categoryCollection->addFieldToFilter('name', $categoryName);
            $categoryCollection->setPageSize(1);

            return $categoryCollection->getFirstItem();
        } catch (\Exception $e) {
            $this->logger->error("Error finding category by name {$categoryName}: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Get all website IDs
     *
     * @return array
     */
    private function getAllWebsiteIds(): array
    {
        $websiteIds = [];
        try {
            $websites = $this->storeManager->getWebsites();
            foreach ($websites as $website) {
                $websiteIds[] = $website->getId();
            }
        } catch (\Exception $e) {
            $this->logger->warning("Failed to get websites from StoreManager, using default website: " . $e->getMessage());
        }

        return $websiteIds;
    }
}
