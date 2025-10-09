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
use Magento\ConfigurableProduct\Api\OptionRepositoryInterface;
use Magento\ConfigurableProduct\Model\Product\Type\Configurable as ConfigurableType;
use Magento\ConfigurableProduct\Helper\Product\Options\Factory as OptionFactory;
use Magento\Eav\Model\Config as EavConfig;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Catalog\Model\ResourceModel\Category\CollectionFactory as CategoryCollectionFactory;
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
     * @var OptionRepositoryInterface
     */
    private $optionRepository;

    /**
     * @var ConfigurableType
     */
    private $configurableType;

    /**
     * @var OptionFactory
     */
    private $optionFactory;

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
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @param ProductRepositoryInterface $productRepository
     * @param ProductFactory $productFactory
     * @param LinkManagementInterface $linkManagement
     * @param OptionRepositoryInterface $optionRepository
     * @param ConfigurableType $configurableType
     * @param OptionFactory $optionFactory
     * @param EavConfig $eavConfig
     * @param StoreManagerInterface $storeManager
     * @param CategoryCollectionFactory $categoryCollectionFactory
     * @param LoggerInterface $logger
     */
    public function __construct(
        ProductRepositoryInterface $productRepository,
        ProductFactory $productFactory,
        LinkManagementInterface $linkManagement,
        OptionRepositoryInterface $optionRepository,
        ConfigurableType $configurableType,
        OptionFactory $optionFactory,
        EavConfig $eavConfig,
        StoreManagerInterface $storeManager,
        CategoryCollectionFactory $categoryCollectionFactory,
        LoggerInterface $logger
    ) {
        $this->productRepository = $productRepository;
        $this->productFactory = $productFactory;
        $this->linkManagement = $linkManagement;
        $this->optionRepository = $optionRepository;
        $this->configurableType = $configurableType;
        $this->optionFactory = $optionFactory;
        $this->eavConfig = $eavConfig;
        $this->storeManager = $storeManager;
        $this->categoryCollectionFactory = $categoryCollectionFactory;
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

            // Save the product first (required for configurable products)
            $this->productRepository->save($product);
            
            // Set configurable options if specified
            if (isset($data['configurable_attributes']) && !empty($data['configurable_attributes'])) {
                $this->setConfigurableOptions($product, $data['configurable_attributes']);
            }
            
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

            // Ensure configurable product has configurable attributes set
            $this->ensureConfigurableAttributes($configurableProduct, $variationSkus);

            // Get existing children
            $existingChildren = $this->linkManagement->getChildren($parentSku);
            $existingChildSkus = [];
            foreach ($existingChildren as $child) {
                $existingChildSkus[] = $child->getSku();
            }

            // Add new children
            $newChildren = array_diff($variationSkus, $existingChildSkus);
            foreach ($newChildren as $variationSku) {
                try {
                    $this->linkManagement->addChild($parentSku, $variationSku);
                    $this->logger->info("Associated variation {$variationSku} with configurable product {$parentSku}");
                } catch (\Exception $e) {
                    $this->logger->warning("Could not associate variation {$variationSku} with {$parentSku}: " . $e->getMessage());
                }
            }

            // Save the configurable product to update associations
            $this->productRepository->save($configurableProduct);

            $this->logger->info("Associated " . count($newChildren) . " new variations with configurable product: {$parentSku}");
        } catch (\Exception $e) {
            $this->logger->error("Error associating variations to {$parentSku}: " . $e->getMessage());
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
     * Ensure configurable product has the necessary configurable attributes
     *
     * @param Product $configurableProduct
     * @param array $variationSkus
     * @return void
     */
    private function ensureConfigurableAttributes(Product $configurableProduct, array $variationSkus): void
    {
        try {
            // Get all unique attributes from variations
            $configurableAttributes = [];
            
            foreach ($variationSkus as $variationSku) {
                try {
                    /** @var Product $variationProduct */
                    $variationProduct = $this->productRepository->get($variationSku);
                    
                    // Check for size attribute
                    if ($variationProduct->getData('size')) {
                        $sizeAttribute = $this->eavConfig->getAttribute(Product::ENTITY, 'size');
                        if ($sizeAttribute->getId()) {
                            $configurableAttributes[] = $sizeAttribute->getId();
                        }
                    }
                    
                    // Check for color attribute
                    if ($variationProduct->getData('color')) {
                        $colorAttribute = $this->eavConfig->getAttribute(Product::ENTITY, 'color');
                        if ($colorAttribute->getId()) {
                            $configurableAttributes[] = $colorAttribute->getId();
                        }
                    }
                    
                    // Check for sport_type attribute
                    if ($variationProduct->getData('sport_type')) {
                        $sportTypeAttribute = $this->eavConfig->getAttribute(Product::ENTITY, 'sport_type');
                        if ($sportTypeAttribute->getId()) {
                            $configurableAttributes[] = $sportTypeAttribute->getId();
                        }
                    }

                    // Check for material attribute
                    if ($variationProduct->getData('material')) {
                        $materialAttribute = $this->eavConfig->getAttribute(Product::ENTITY, 'material');
                        if ($materialAttribute->getId()) {
                            $configurableAttributes[] = $materialAttribute->getId();
                        }
                    }

                } catch (\Exception $e) {
                    $this->logger->warning("Could not fetch variation product {$variationSku}: " . $e->getMessage());
                }
            }

            // Remove duplicates
            $configurableAttributes = array_unique($configurableAttributes);

            if (!empty($configurableAttributes)) {
                // Set the configurable attributes
                $configurableProduct->setCanSaveConfigurableAttributes(true);
                $configurableProduct->setAffectConfigurableProductAttributes($configurableAttributes);

                // Save the product with configurable attributes
                $this->productRepository->save($configurableProduct);

                $this->logger->info("Set configurable attributes for {$configurableProduct->getSku()}: " . implode(',', $configurableAttributes));
            }
        } catch (\Exception $e) {
            $this->logger->error("Error ensuring configurable attributes for {$configurableProduct->getSku()}: " . $e->getMessage());
        }
    }

    /**
     * Set configurable options for the product
     *
     * @param Product $product
     * @param string $configurableAttributesString
     * @return void
     */
    private function setConfigurableOptions(Product $product, string $configurableAttributesString): void
    {
        try {
            $configurableAttributeCodes = explode(',', $configurableAttributesString);
            $configurableAttributeCodes = array_map('trim', $configurableAttributeCodes);
            
            $configurableAttributes = [];
            
            foreach ($configurableAttributeCodes as $attributeCode) {
                $attribute = $this->eavConfig->getAttribute(Product::ENTITY, $attributeCode);
                if ($attribute->getId()) {
                    $configurableAttributes[] = $attribute->getId();
                }
            }
            
            if (!empty($configurableAttributes)) {
                // Set the configurable attributes
                $product->setCanSaveConfigurableAttributes(true);
                $product->setAffectConfigurableProductAttributes($configurableAttributes);
                
                // Save the product with configurable attributes
                $this->productRepository->save($product);
                
                $this->logger->info("Set configurable options for {$product->getSku()}: " . implode(',', $configurableAttributeCodes));
            }
        } catch (\Exception $e) {
            $this->logger->error("Error setting configurable options for {$product->getSku()}: " . $e->getMessage());
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
