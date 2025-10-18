<?php
/**
 * Copyright © Escooter. All rights reserved.
 * See LICENSE.txt for license details.
 */

namespace Escooter\Migration\Helper;

use Magento\Catalog\Api\CategoryRepositoryInterface;
use Magento\Catalog\Model\CategoryFactory;
use Magento\Catalog\Model\Category;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Catalog\Model\ResourceModel\Category\CollectionFactory as CategoryCollectionFactory;
use Psr\Log\LoggerInterface;

/**
 * Helper class for importing and managing categories
 */
class CategoryImporterHelper
{
    /**
     * @var CategoryRepositoryInterface
     */
    private $categoryRepository;

    /**
     * @var CategoryFactory
     */
    private $categoryFactory;

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
     * @var array
     */
    private $createdCategories = [];

    /**
     * @param CategoryRepositoryInterface $categoryRepository
     * @param CategoryFactory $categoryFactory
     * @param StoreManagerInterface $storeManager
     * @param CategoryCollectionFactory $categoryCollectionFactory
     * @param LoggerInterface $logger
     */
    public function __construct(
        CategoryRepositoryInterface $categoryRepository,
        CategoryFactory $categoryFactory,
        StoreManagerInterface $storeManager,
        CategoryCollectionFactory $categoryCollectionFactory,
        LoggerInterface $logger
    ) {
        $this->categoryRepository = $categoryRepository;
        $this->categoryFactory = $categoryFactory;
        $this->storeManager = $storeManager;
        $this->categoryCollectionFactory = $categoryCollectionFactory;
        $this->logger = $logger;
    }

    /**
     * Create sportswear categories from data
     *
     * @param array $categoryData
     * @return void
     * @throws \Exception
     */
    public function createSportswearCategories(array $categoryData): void
    {
        try {
            // Group categories by path to maintain hierarchy
            $categoryPaths = [];
            foreach ($categoryData as $data) {
                $path = $data['category_path'];
                $categoryPaths[$path] = $data;
            }

            // Create categories in order (parents first)
            $this->createCategoriesInOrder($categoryPaths);

            $this->logger->info('Sportswear categories created successfully');

        } catch (\Exception $e) {
            $this->logger->error('Error creating sportswear categories: ' . $e->getMessage());
            throw new \Exception('Error creating sportswear categories: ' . $e->getMessage());
        }
    }


    /**
     * Create categories in order (parents first)
     *
     * @param array $categoryPaths
     * @return void
     */
    private function createCategoriesInOrder(array $categoryPaths): void
    {
        // Sort paths by depth (shorter paths first)
        $sortedPaths = $this->sortPathsByDepth($categoryPaths);
        
        foreach ($sortedPaths as $path => $categoryData) {
            $this->createCategoryFromData($categoryData);
        }
    }

    /**
     * Sort category paths by depth (parents first)
     *
     * @param array $categoryPaths
     * @return array
     */
    private function sortPathsByDepth(array $categoryPaths): array
    {
        $sortedPaths = [];
        $pathDepths = [];
        
        foreach ($categoryPaths as $path => $data) {
            $depth = substr_count($path, '/');
            $pathDepths[$path] = $depth;
        }
        
        asort($pathDepths);
        
        foreach ($pathDepths as $path => $depth) {
            $sortedPaths[$path] = $categoryPaths[$path];
        }
        
        return $sortedPaths;
    }

    /**
     * Create category from CSV data
     *
     * @param array $categoryData
     * @return Category
     * @throws \Exception
     */
    private function createCategoryFromData(array $categoryData): Category
    {
        try {
            $path = $categoryData['category_path'];
            $name = $categoryData['name_en'];
            
            // Get parent ID
            $parentId = $this->getParentIdFromPath($path);
            
            // Check if category already exists
            $existingCategory = $this->findCategoryByName($name, $parentId);
            if ($existingCategory) {
                $this->logger->info("Category '{$name}' already exists");
                return $existingCategory;
            }
            
            // Create base category with default attributes (store ID 0)
            $category = $this->categoryFactory->create();
            $category->setStoreId(0); // Set to default store view
            $category->setName($name);
            $category->setParentId($parentId);
            $category->setIsActive($categoryData['is_active'] ?? 1);
            $category->setIncludeInMenu($categoryData['include_in_menu'] ?? 1);
            $category->setDisplayMode($categoryData['display_mode'] ?? Category::DM_PRODUCT);
            $category->setIsAnchor($categoryData['is_anchor'] ?? 1);
            $category->setPosition($categoryData['position'] ?? 1);
            
            // Set URL key
            $urlKey = $categoryData['url_key_en'] ?? strtolower(str_replace(' ', '-', $name));
            $category->setUrlKey($urlKey);
            
            // Set meta information for default store
            $category->setMetaTitle($categoryData['meta_title_en'] ?? $name);
            $category->setMetaDescription($categoryData['meta_description_en'] ?? "Shop {$name} - High-quality sportswear and athletic gear");
            
            // Save the base category
            $this->categoryRepository->save($category);
            
            // Iterate through store views and set localized attributes
            $this->setLocalizedCategoryAttributes($category, $categoryData);
            
            $this->createdCategories[$name] = $category;
            
            return $category;
            
        } catch (\Exception $e) {
            $this->logger->error("Error creating category {$name}: " . $e->getMessage());
            throw new \Exception("Error creating category {$name}: " . $e->getMessage());
        }
    }

    /**
     * Create a category
     *
     * @param array $data
     * @return Category
     * @throws \Exception
     */
    public function createCategory(array $data): Category
    {
        try {
            // Check if category already exists
            $existingCategory = $this->findCategoryByName($data['name'], $data['parent_id']);
            if ($existingCategory) {
                $this->logger->info("Category '{$data['name']}' already exists");
                return $existingCategory;
            }

            $category = $this->categoryFactory->create();
            $category->setName($data['name']);
            $category->setParentId($data['parent_id']);
            $category->setIsActive($data['is_active'] ?? true);
            $category->setIncludeInMenu($data['include_in_menu'] ?? true);
            $category->setUrlKey($data['url_key']);
            $category->setStoreId(0); // Store ID 0 for admin

            $this->categoryRepository->save($category);
            $this->logger->info("Created category: {$data['name']}");

            return $category;

        } catch (\Exception $e) {
            $this->logger->error("Error creating category '{$data['name']}': " . $e->getMessage());
            throw new \Exception("Error creating category '{$data['name']}': " . $e->getMessage());
        }
    }


    /**
     * Get category ID by name
     *
     * @param string $categoryName
     * @return int|null
     */
    public function getCategoryIdByName(string $categoryName): ?int
    {
        $category = $this->findCategoryByNameOnly($categoryName);
        return $category ? $category->getId() : null;
    }

    /**
     * Get all category IDs including parent categories
     *
     * @param string $categoryName
     * @return array
     */
    public function getCategoryIdsWithParents(string $categoryName): array
    {
        $category = $this->findCategoryByNameOnly($categoryName);
        if (!$category) {
            return [];
        }

        $categoryIds = [];

        // Get the category path (includes all parent categories)
        $path = $category->getPath();
        $pathIds = explode('/', $path);

        // Remove root category (ID 1) and default category (ID 2) from path
        $pathIds = array_filter($pathIds, function ($id) {
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
     * Set localized category attributes for all store views
     *
     * @param Category $category
     * @param array $categoryData
     * @return void
     */
    private function setLocalizedCategoryAttributes(Category $category, array $categoryData): void
    {
        try {
            $stores = $this->storeManager->getStores();
            foreach ($stores as $store) {
                $storeId = $store->getId();
                if ($storeId == 0) { // Skip default store view as it's already set
                    continue;
                }

                try {
                    $category->setStoreId($storeId);
                    
                    // Set localized name based on store code
                    $localizedName = $this->getLocalizedName($categoryData, $store->getCode());
                    if ($localizedName) {
                        $category->setName($localizedName);
                    }
                    
                    // Set localized URL key with fallback
                    $localizedUrlKey = $this->getLocalizedUrlKey($categoryData, $store->getCode());
                    if ($localizedUrlKey) {
                        // Check if URL key already exists for this store
                        if ($this->isUrlKeyAvailable($localizedUrlKey, $storeId, $category->getId())) {
                            $category->setUrlKey($localizedUrlKey);
                        } else {
                            // Generate unique URL key by appending store code
                            $uniqueUrlKey = $localizedUrlKey . '-' . $store->getCode();
                            $category->setUrlKey($uniqueUrlKey);
                        }
                    }
                    
                    // Set localized meta title
                    $localizedMetaTitle = $this->getLocalizedMetaTitle($categoryData, $store->getCode());
                    if ($localizedMetaTitle) {
                        $category->setMetaTitle($localizedMetaTitle);
                    }
                    
                    // Set localized meta description
                    $localizedMetaDescription = $this->getLocalizedMetaDescription($categoryData, $store->getCode());
                    if ($localizedMetaDescription) {
                        $category->setMetaDescription($localizedMetaDescription);
                    }
                    
                    // Save the localized attributes
                    $category->save();
                } catch (\Exception $e) {
                    // Log the error but continue with other stores
                    $this->logger->warning("Error setting localized attributes for store {$storeId}: " . $e->getMessage());
                    continue;
                }
            }
        } catch (\Exception $e) {
            $this->logger->error("Error setting localized attributes: " . $e->getMessage());
        }
    }

    /**
     * Check if URL key is available for the given store
     *
     * @param string $urlKey
     * @param int $storeId
     * @param int $categoryId
     * @return bool
     */
    private function isUrlKeyAvailable(string $urlKey, int $storeId, int $categoryId): bool
    {
        try {
            $categoryCollection = $this->categoryCollectionFactory->create();
            $categoryCollection->addFieldToFilter('url_key', $urlKey);
            $categoryCollection->addFieldToFilter('store_id', $storeId);
            $categoryCollection->addFieldToFilter('entity_id', ['neq' => $categoryId]);
            
            return $categoryCollection->getSize() == 0;
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Get localized name based on store code
     *
     * @param array $categoryData
     * @param string $storeCode
     * @return string|null
     */
    private function getLocalizedName(array $categoryData, string $storeCode): ?string
    {
        $nameField = 'name_' . $storeCode;
        return $categoryData[$nameField] ?? null;
    }

    /**
     * Get localized URL key based on store code
     *
     * @param array $categoryData
     * @param string $storeCode
     * @return string|null
     */
    private function getLocalizedUrlKey(array $categoryData, string $storeCode): ?string
    {
        $urlKeyField = 'url_key_' . $storeCode;
        return $categoryData[$urlKeyField] ?? null;
    }

    /**
     * Get localized meta title based on store code
     *
     * @param array $categoryData
     * @param string $storeCode
     * @return string|null
     */
    private function getLocalizedMetaTitle(array $categoryData, string $storeCode): ?string
    {
        $metaTitleField = 'meta_title_' . $storeCode;
        return $categoryData[$metaTitleField] ?? null;
    }

    /**
     * Get localized meta description based on store code
     *
     * @param array $categoryData
     * @param string $storeCode
     * @return string|null
     */
    private function getLocalizedMetaDescription(array $categoryData, string $storeCode): ?string
    {
        $metaDescriptionField = 'meta_description_' . $storeCode;
        return $categoryData[$metaDescriptionField] ?? null;
    }

    /**
     * Get parent ID from category path
     *
     * @param string $path
     * @return int
     */
    private function getParentIdFromPath(string $path): int
    {
        $pathParts = explode('/', $path);
        
        // Remove "Default Category" from path
        if ($pathParts[0] === 'Default Category') {
            array_shift($pathParts);
        }
        
        if (empty($pathParts)) {
            return 2; // Root category (ID 2)
        }
        
        // If only one part, parent is root category (ID 2)
        if (count($pathParts) === 1) {
            return 2;
        }
        
        // Find parent category by looking for the parent path
        $parentPathParts = array_slice($pathParts, 0, -1);
        $parentName = end($parentPathParts);
        
        // Look for parent in created categories
        foreach ($this->createdCategories as $name => $category) {
            if ($name === $parentName) {
                return $category->getId();
            }
        }
        
        // If not found, return root category (ID 2)
        return 2;
    }

    /**
     * Find category by name and parent
     *
     * @param string $name
     * @param int $parentId
     * @return Category|null
     */
    private function findCategoryByName(string $name, int $parentId): ?Category
    {
        try {
            $category = $this->categoryFactory->create();
            $category->getResource()->load($category, $name, 'name');

            if ($category->getId() && $category->getParentId() == $parentId) {
                return $category;
            }
        } catch (\Exception $e) {
            // Category not found, continue
        }

        return null;
    }

    /**
     * Find category by name only
     *
     * @param string $categoryName
     * @return Category|null
     */
    private function findCategoryByNameOnly(string $categoryName): ?Category
    {
        try {
            $categoryCollection = $this->categoryCollectionFactory->create();
            $categoryCollection->addFieldToFilter('name', $categoryName);
            $categoryCollection->setPageSize(1);

            if ($categoryCollection->getSize() > 0) {
                return $categoryCollection->getFirstItem();
            }
        } catch (\Exception $e) {
            $this->logger->warning("Error finding category '{$categoryName}': " . $e->getMessage());
        }

        return null;
    }

    /**
     * Get created categories
     *
     * @return array
     */
    public function getCreatedCategories(): array
    {
        return $this->createdCategories;
    }
}
