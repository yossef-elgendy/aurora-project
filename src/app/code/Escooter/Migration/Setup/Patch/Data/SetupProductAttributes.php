<?php
/**
 * Copyright © Escooter. All rights reserved.
 * See LICENSE.txt for license details.
 */

namespace Escooter\Migration\Setup\Patch\Data;

use Magento\Framework\Setup\Patch\DataPatchInterface;
use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\Eav\Setup\EavSetupFactory;
use Magento\Eav\Setup\EavSetup;
use Magento\Eav\Model\Entity\Attribute\ScopedAttributeInterface;
use Magento\Catalog\Model\Product;
use Magento\Eav\Model\Entity\Attribute\Backend\ArrayBackend;
use Magento\Eav\Model\Entity\Attribute\Source\Table;

/**
 * Data patch to setup product attributes for sportswear catalog
 */
class SetupProductAttributes implements DataPatchInterface
{
    /**
     * @var ModuleDataSetupInterface
     */
    private $moduleDataSetup;

    /**
     * @var EavSetupFactory
     */
    private $eavSetupFactory;

    /**
     * @param ModuleDataSetupInterface $moduleDataSetup
     * @param EavSetupFactory $eavSetupFactory
     */
    public function __construct(
        ModuleDataSetupInterface $moduleDataSetup,
        EavSetupFactory $eavSetupFactory
    ) {
        $this->moduleDataSetup = $moduleDataSetup;
        $this->eavSetupFactory = $eavSetupFactory;
    }

    /**
     * {@inheritdoc}
     */
    public function apply()
    {
        $this->moduleDataSetup->getConnection()->startSetup();

        /** @var EavSetup $eavSetup */
        $eavSetup = $this->eavSetupFactory->create(['setup' => $this->moduleDataSetup]);

        try {
            // Create size attribute
            $this->createSizeAttribute($eavSetup);

            // Create color attribute
            $this->createColorAttribute($eavSetup);

            // Create sport type attribute
            $this->createSportTypeAttribute($eavSetup);

            // Create material attribute
            $this->createMaterialAttribute($eavSetup);

        } catch (\Exception $e) {
            throw new \Exception('Error setting up product attributes: ' . $e->getMessage());
        }

        $this->moduleDataSetup->getConnection()->endSetup();
    }

    /**
     * Create size attribute
     */
    private function createSizeAttribute($eavSetup)
    {
        $eavSetup->addAttribute(
            Product::ENTITY,
            'size',
            [
                'type' => 'varchar',
                'label' => 'Size',
                'input' => 'select',
                'source' => Table::class,
                'required' => false,
                'visible_on_front' => true,
                'global' => ScopedAttributeInterface::SCOPE_GLOBAL,
                'group' => 'General',
                'backend' => ArrayBackend::class,
                'sort_order' => 10,
                'is_used_in_grid' => true,
                'is_visible_in_grid' => true,
                'is_filterable_in_grid' => true,
                'is_user_defined' => true
            ]
        );

        $attributeId = $eavSetup->getAttributeId(Product::ENTITY, 'size');
        $sizeOptions = [
            'XS', 'S', 'M', 'L', 'XL', 'XXL', 'XXXL',
            'Small', 'Medium', 'Large',
            '28', '30', '32', '34', '36', '38', '40', '42', '44', '46', '48',
            '6', '7', '8', '9', '10', '11', '12', '13', '14', '15'
        ];

        foreach ($sizeOptions as $index => $option) {
            $eavSetup->addAttributeOption([
                'attribute_id' => $attributeId,
                'value' => [
                    'option' => [$option, $option]
                ],
                'order' => [$index + 1]
            ]);
        }
    }

    /**
     * Create color attribute
     */
    private function createColorAttribute($eavSetup)
    {
        $eavSetup->addAttribute(
            Product::ENTITY,
            'color',
            [
                'type' => 'varchar',
                'label' => 'Color',
                'input' => 'select',
                'source' => Table::class,
                'required' => false,
                'visible_on_front' => true,
                'global' => ScopedAttributeInterface::SCOPE_GLOBAL,
                'group' => 'General',
                'backend' => ArrayBackend::class,
                'sort_order' => 20,
                'is_used_in_grid' => true,
                'is_visible_in_grid' => true,
                'is_filterable_in_grid' => true,
                'is_user_defined' => true
            ]
        );

        $attributeId = $eavSetup->getAttributeId(Product::ENTITY, 'color');
        $colorOptions = [
            'Black', 'White', 'Red', 'Blue', 'Green', 'Yellow', 'Orange', 'Purple',
            'Pink', 'Grey', 'Brown', 'Navy', 'Maroon', 'Teal', 'Cyan', 'Magenta',
            'Lime', 'Olive', 'Silver', 'Gold'
        ];

        foreach ($colorOptions as $index => $color) {
            $eavSetup->addAttributeOption([
                'attribute_id' => $attributeId,
                'value' => [
                    'option' => [$color, $color]
                ],
                'order' => [$index + 1]
            ]);
        }
    }

    /**
     * Create sport type attribute
     */
    private function createSportTypeAttribute($eavSetup)
    {
        $eavSetup->addAttribute(
            Product::ENTITY,
            'sport_type',
            [
                'type' => 'varchar',
                'label' => 'Sport Type',
                'input' => 'select',
                'source' => Table::class,
                'required' => false,
                'visible_on_front' => true,
                'global' => ScopedAttributeInterface::SCOPE_GLOBAL,
                'group' => 'General',
                'backend' => ArrayBackend::class,
                'sort_order' => 30,
                'is_used_in_grid' => true,
                'is_visible_in_grid' => true,
                'is_filterable_in_grid' => true,
                'is_user_defined' => true
            ]
        );

        $attributeId = $eavSetup->getAttributeId(Product::ENTITY, 'sport_type');
        $sportTypes = [
            'Running', 'Yoga', 'Gym', 'Cross Training', 'Cycling', 'Swimming',
            'Tennis', 'Basketball', 'Football', 'Soccer', 'Baseball', 'Golf',
            'Hiking', 'Climbing', 'Dancing', 'Pilates', 'Boxing', 'Martial Arts'
        ];

        foreach ($sportTypes as $index => $sport) {
            $eavSetup->addAttributeOption([
                'attribute_id' => $attributeId,
                'value' => [
                    'option' => [$sport, $sport]
                ],
                'order' => [$index + 1]
            ]);
        }
    }

    /**
     * Create material attribute
     */
    private function createMaterialAttribute($eavSetup)
    {
        $eavSetup->addAttribute(
            Product::ENTITY,
            'material',
            [
                'type' => 'varchar',
                'label' => 'Material',
                'input' => 'select',
                'source' => Table::class,
                'required' => false,
                'visible_on_front' => true,
                'global' => ScopedAttributeInterface::SCOPE_GLOBAL,
                'group' => 'General',
                'backend' => ArrayBackend::class,
                'sort_order' => 40,
                'is_used_in_grid' => true,
                'is_visible_in_grid' => true,
                'is_filterable_in_grid' => true,
                'is_user_defined' => true
            ]
        );

        $attributeId = $eavSetup->getAttributeId(Product::ENTITY, 'material');
        $materials = [
            'Polyester', 'Cotton', 'Spandex', 'Nylon', 'Bamboo', 'Merino Wool',
            'Synthetic Blend', 'Mesh', 'Compression Fabric', 'Moisture-Wicking',
            'Breathable', 'UV Protection', 'Quick-Dry', 'Anti-Microbial'
        ];

        foreach ($materials as $index => $material) {
            $eavSetup->addAttributeOption([
                'attribute_id' => $attributeId,
                'value' => [
                    'option' => [$material, $material]
                ],
                'order' => [$index + 1]
            ]);
        }
    }

    /**
     * {@inheritdoc}
     */
    public static function getDependencies()
    {
        return [
            ConfigureStoreLocales::class
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
