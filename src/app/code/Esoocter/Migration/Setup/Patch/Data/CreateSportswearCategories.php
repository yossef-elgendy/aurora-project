<?php
/**
 * Copyright © Esoocter. All rights reserved.
 * See LICENSE.txt for license details.
 */

namespace Esoocter\Migration\Setup\Patch\Data;

use Magento\Framework\Setup\Patch\DataPatchInterface;
use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\Catalog\Api\CategoryRepositoryInterface;
use Magento\Catalog\Model\CategoryFactory;
use Magento\Catalog\Model\Category;
use Magento\Catalog\Model\ResourceModel\Category\CollectionFactory as CategoryCollectionFactory;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Exception\CouldNotSaveException;
use Magento\Framework\File\Csv;
use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\Filesystem;

/**
 * Data patch to create sportswear categories
 */
class CreateSportswearCategories implements DataPatchInterface
{
    private const ROOT_CATEGORY_ID = 2;

    /**
     * @var ModuleDataSetupInterface
     */
    private $moduleDataSetup;

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
     * @var array
     */
    private $createdCategories = [];

    /**
     * @param ModuleDataSetupInterface $moduleDataSetup
     * @param CategoryRepositoryInterface $categoryRepository
     * @param CategoryFactory $categoryFactory
     * @param StoreManagerInterface $storeManager
     * @param Csv $csvProcessor
     * @param Filesystem $filesystem
     * @param CategoryCollectionFactory $categoryCollectionFactory
     */
    public function __construct(
        ModuleDataSetupInterface $moduleDataSetup,
        CategoryRepositoryInterface $categoryRepository,
        CategoryFactory $categoryFactory,
        StoreManagerInterface $storeManager,
        Csv $csvProcessor,
        Filesystem $filesystem,
        CategoryCollectionFactory $categoryCollectionFactory
    ) {
        $this->moduleDataSetup = $moduleDataSetup;
        $this->categoryRepository = $categoryRepository;
        $this->categoryFactory = $categoryFactory;
        $this->storeManager = $storeManager;
        $this->csvProcessor = $csvProcessor;
        $this->filesystem = $filesystem;
        $this->categoryCollectionFactory = $categoryCollectionFactory;
    }

    /**
     * {@inheritdoc}
     */
    public function apply()
    {
        $this->moduleDataSetup->getConnection()->startSetup();

        try {
            // Create categories from CSV data
            $this->createCategoriesFromCsv();
        } catch (\Exception $e) {
            throw new \Exception('Error creating sportswear categories: ' . $e->getMessage());
        }

        $this->moduleDataSetup->getConnection()->endSetup();
    }

    /**
     * Create categories from CSV data
     */
    private function createCategoriesFromCsv()
    {
        $csvFile = $this->getCsvFilePath('categories.csv');

        if (!file_exists($csvFile)) {
            throw new \Exception("CSV file not found: {$csvFile}");
        }

        $data = $this->csvProcessor->getData($csvFile);
        $headers = array_shift($data);

        // Group categories by path to maintain hierarchy
        $categoryPaths = [];
        foreach ($data as $row) {
            $categoryData = array_combine($headers, $row);
            $path = $categoryData['category_path'];
            $categoryPaths[$path] = $categoryData;
        }

        // Create categories in order (parents first)
        $this->createCategoriesInOrder($categoryPaths);
    }

    /**
     * Create categories in order (parents first)
     */
    private function createCategoriesInOrder($categoryPaths)
    {
        // Sort paths by depth (shorter paths first)
        $sortedPaths = $this->sortPathsByDepth($categoryPaths);
        
        foreach ($sortedPaths as $path => $categoryData) {
            $this->createCategoryFromData($categoryData);
        }
    }

    /**
     * Sort category paths by depth (parents first)
     */
    private function sortPathsByDepth($categoryPaths)
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
     */
    private function createCategoryFromData($categoryData)
    {
        try {
            $path = $categoryData['category_path'];
            $name = $categoryData['name_en'];
            
            // Get parent ID
            $parentId = $this->getParentIdFromPath($path);
            
            // Check if category already exists
            $existingCategory = $this->findCategoryByName($name, $parentId);
            if ($existingCategory) {
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
            throw new \Exception("Error creating category {$name}: " . $e->getMessage());
        }
    }

    /**
     * Set localized category attributes for all store views
     */
    private function setLocalizedCategoryAttributes($category, $categoryData)
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
                    error_log("Error setting localized attributes for store {$storeId}: " . $e->getMessage());
                    continue;
                }
            }
        } catch (\Exception $e) {
            throw new \Exception("Error setting localized attributes: " . $e->getMessage());
        }
    }

    /**
     * Check if URL key is available for the given store
     */
    private function isUrlKeyAvailable($urlKey, $storeId, $categoryId)
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
     */
    private function getLocalizedName($categoryData, $storeCode)
    {
        $nameField = 'name_' . $storeCode;
        return $categoryData[$nameField] ?? null;
    }

    /**
     * Get localized URL key based on store code
     */
    private function getLocalizedUrlKey($categoryData, $storeCode)
    {
        $urlKeyField = 'url_key_' . $storeCode;
        return $categoryData[$urlKeyField] ?? null;
    }

    /**
     * Get localized meta title based on store code
     */
    private function getLocalizedMetaTitle($categoryData, $storeCode)
    {
        $metaTitleField = 'meta_title_' . $storeCode;
        return $categoryData[$metaTitleField] ?? null;
    }

    /**
     * Get localized meta description based on store code
     */
    private function getLocalizedMetaDescription($categoryData, $storeCode)
    {
        $metaDescriptionField = 'meta_description_' . $storeCode;
        return $categoryData[$metaDescriptionField] ?? null;
    }

    /**
     * Get parent ID from category path
     */
    private function getParentIdFromPath($path)
    {
        $pathParts = explode('/', $path);
        
        // Remove "Default Category" from path
        if ($pathParts[0] === 'Default Category') {
            array_shift($pathParts);
        }
        
        if (empty($pathParts)) {
            return self::ROOT_CATEGORY_ID; // Root category (ID 2)
        }
        
        // If only one part, parent is root category (ID 2)
        if (count($pathParts) === 1) {
            return self::ROOT_CATEGORY_ID;
        }
        
        // Find parent category by looking for the parent path
        $parentPathParts = array_slice($pathParts, 0, -1);
        $parentPath = implode('/', $parentPathParts);
        $parentName = end($parentPathParts);
        
        // Look for parent in created categories
        foreach ($this->createdCategories as $name => $category) {
            if ($name === $parentName) {
                return $category->getId();
            }
        }
        
        // If not found, return root category (ID 2)
        return self::ROOT_CATEGORY_ID;
    }

    /**
     * Get CSV file path
     */
    private function getCsvFilePath($filename)
    {
        $moduleDir = $this->filesystem->getDirectoryRead(DirectoryList::APP);
        return $moduleDir->getAbsolutePath('code/Esoocter/Migration/Setup/Patch/Data/csv/' . $filename);
    }


    /**
     * Find category by name and parent
     */
    private function findCategoryByName($name, $parentId)
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
     * Get created categories
     */
    public function getCreatedCategories()
    {
        return $this->createdCategories;
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
