<?php
/**
 * Copyright © Esoocter. All rights reserved.
 * See LICENSE.txt for license details.
 */

namespace Esoocter\Migration\Helper;

use Magento\Framework\File\Csv;
use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\Filesystem;
use Psr\Log\LoggerInterface;

/**
 * Helper class for importing CSV data
 */
class CsvImporterHelper
{
    /**
     * @var Csv
     */
    private $csvProcessor;

    /**
     * @var Filesystem
     */
    private $filesystem;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @param Csv $csvProcessor
     * @param Filesystem $filesystem
     * @param LoggerInterface $logger
     */
    public function __construct(
        Csv $csvProcessor,
        Filesystem $filesystem,
        LoggerInterface $logger
    ) {
        $this->csvProcessor = $csvProcessor;
        $this->filesystem = $filesystem;
        $this->logger = $logger;
    }

    /**
     * Import data from CSV file
     *
     * @param string $filename
     * @return array
     * @throws \Exception
     */
    public function importCsvData(string $filename): array
    {
        $csvFile = $this->getCsvFilePath($filename);
        return $this->processCsvFile($csvFile, $filename);
    }


    /**
     * Process CSV file and return data
     *
     * @param string $csvFile
     * @param string $filename
     * @return array
     * @throws \Exception
     */
    private function processCsvFile(string $csvFile, string $filename): array
    {
        try {
            if (!file_exists($csvFile)) {
                throw new \Exception("CSV file not found: {$csvFile}");
            }

            $data = $this->csvProcessor->getData($csvFile);
            $headers = array_shift($data);
            $processedData = [];

            foreach ($data as $row) {
                // Ensure row has same number of elements as headers
                if (count($row) === count($headers)) {
                    $processedData[] = array_combine($headers, $row);
                } else {
                    $this->logger->warning("CSV row mismatch in {$filename}. Headers: " . count($headers) . ", Row: " . count($row));
                }
            }

            $this->logger->info("Processed {$filename}: " . count($processedData) . " rows");
            return $processedData;

        } catch (\Exception $e) {
            $this->logger->error("Error processing CSV file {$filename}: " . $e->getMessage());
            throw new \Exception("Error processing CSV file {$filename}: " . $e->getMessage());
        }
    }

    /**
     * Get CSV file path
     *
     * @param string $filename
     * @return string
     */
    private function getCsvFilePath(string $filename): string
    {
        $moduleDir = $this->filesystem->getDirectoryRead(DirectoryList::APP);
        return $moduleDir->getAbsolutePath() . 'code/Esoocter/Migration/Setup/Patch/Data/csv/' . $filename;
    }

    /**
     * Group variations by parent SKU
     *
     * @param array $variations
     * @return array
     */
    public function groupVariationsByParent(array $variations): array
    {
        $variationsByParent = [];

        foreach ($variations as $variation) {
            if (isset($variation['parent_sku']) && !empty($variation['parent_sku'])) {
                $parentSku = $variation['parent_sku'];
                if (!isset($variationsByParent[$parentSku])) {
                    $variationsByParent[$parentSku] = [];
                }
                $variationsByParent[$parentSku][] = $variation['sku'];
            }
        }

        return $variationsByParent;
    }
}
