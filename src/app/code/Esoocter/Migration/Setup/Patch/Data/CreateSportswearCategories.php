<?php
/**
 * Copyright © Esoocter. All rights reserved.
 * See LICENSE.txt for license details.
 */

namespace Esoocter\Migration\Setup\Patch\Data;

use Magento\Framework\Setup\Patch\DataPatchInterface;
use Magento\Framework\Setup\ModuleDataSetupInterface;
use Esoocter\Migration\Helper\CategoryImporterHelper;
use Esoocter\Migration\Helper\CsvImporterHelper;
use Psr\Log\LoggerInterface;

/**
 * Data patch to create sportswear categories
 */
class CreateSportswearCategories implements DataPatchInterface
{
    /**
     * @var ModuleDataSetupInterface
     */
    private $moduleDataSetup;

    /**
     * @var CategoryImporterHelper
     */
    private $categoryImporterHelper;

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
     * @param CategoryImporterHelper $categoryImporterHelper
     * @param CsvImporterHelper $csvImporterHelper
     * @param LoggerInterface $logger
     */
    public function __construct(
        ModuleDataSetupInterface $moduleDataSetup,
        CategoryImporterHelper $categoryImporterHelper,
        CsvImporterHelper $csvImporterHelper,
        LoggerInterface $logger
    ) {
        $this->moduleDataSetup = $moduleDataSetup;
        $this->categoryImporterHelper = $categoryImporterHelper;
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
            // Import categories from CSV
            $categoryData = $this->csvImporterHelper->importCsvData('categories.csv');

            // Create sportswear categories using helper
            $this->categoryImporterHelper->createSportswearCategories($categoryData);
            $this->logger->info('Sportswear categories created successfully');
        } catch (\Exception $e) {
            $this->logger->error('Error creating sportswear categories: ' . $e->getMessage());
            throw new \Exception('Error creating sportswear categories: ' . $e->getMessage());
        }

        $this->moduleDataSetup->getConnection()->endSetup();
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
