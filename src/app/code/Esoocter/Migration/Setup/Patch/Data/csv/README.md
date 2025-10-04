# Sportswear Catalog Import
This directory contains CSV files for importing a comprehensive sportswear catalog with 50+ SKUs including simple and configurable products with localized content and simplified currency management.

## CSV Files Overview

### 1. Simple Products (`simple_products.csv`)
Contains 20 simple sportswear products including:
- Performance running t-shirts
- Training tank tops
- Athletic shorts and leggings
- Windbreaker jackets
- Sports bras
- Athletic socks
- Performance caps
- Workout gloves
- Headbands
- Compression wear
- UV protection clothing

### 2. Configurable Products (`configurable_products.csv`)
Contains 5 configurable products with size and color variations:
- Performance Running Shoes
- Training Hoodie
- Athletic Joggers
- Performance Sweatshirt
- Yoga Leggings

### 3. Configurable Variations (`configurable_variations.csv`)
Contains 30+ product variations for configurable products with different sizes and colors:
- Small, Medium, Large sizes
- Black, White, Grey, Navy, Red, Purple colors
- Proper stock management for each variation

### 4. Localized Content
Localized product information for different stores:
- `localized_products_fr.csv` - French translations
- `localized_products_it.csv` - Italian translations
- `localized_products_ie.csv` - Irish (English) content

### 5. Currency Configuration
**Note**: Currency-specific pricing is now handled automatically through the SetupCurrencyPricing data patch. The `currency_pricing.csv` file is no longer used.

## Product Categories

The catalog includes products in the following categories:
- **Tops**: T-shirts, tank tops, sweatshirts, compression shirts
- **Bottoms**: Shorts, leggings, joggers, compression shorts
- **Outerwear**: Jackets, hoodies, windbreakers
- **Underwear**: Sports bras
- **Accessories**: Socks, caps, gloves, headbands
- **Shoes**: Running shoes

## Product Features

### Simple Products
- High-performance materials
- Moisture-wicking technology
- UV protection
- Compression support
- Breathable fabrics
- Quick-dry properties

### Configurable Products
- Size variations (Small, Medium, Large)
- Color options (Black, White, Grey, Navy, Red, Purple)
- Proper stock management
- Individual pricing per variation


### Base Currency (USD)
- All products are priced in USD as the base currency
- Automatic currency conversion based on configured exchange rates
- Simplified pricing management

### Currency Conversion Rates
- **USD to GBP**: 0.8 (1 USD = 0.8 GBP)
- **USD to EUR**: 0.9 (1 USD = 0.9 EUR)
- **GBP to USD**: 1.25 (1 GBP = 1.25 USD)
- **EUR to USD**: 1.11 (1 EUR = 1.11 USD)

### Pricing Examples
- **Performance Running T-Shirt**: $29.99 USD → £23.99 GBP → €26.99 EUR
- **Training Hoodie**: $69.99 USD → £55.99 GBP → €62.99 EUR
- **Performance Running Shoes**: $129.99 USD → £103.99 GBP → €116.99 EUR

## Localization

Products are localized for multiple markets:
- **English (UK)**: Default content
- **French**: Complete French translations
- **Italian**: Complete Italian translations
- **Irish**: English content with Irish market focus

## Data Patch Structure

The import is handled by four main data patches:

1. **SetupProductAttributes**: Creates necessary product attributes (size, color, sport type, material)
2. **CreateSportswearCategories**: Creates the sportswear category hierarchy
3. **ImportSportswearCatalog**: Imports simple and configurable products from CSV files with proper localization
4. **SetupCurrencyPricing**: Sets up currency conversion rates and catalog price scope configuration

## Usage

To import the catalog, run the data patches:

```bash
bin/magento setup:upgrade
```

This will:
1. Create product attributes (size, color, sport type, material)
2. Create sportswear category hierarchy
3. Import simple products with USD base pricing
4. Create configurable products and associate them with their variations
5. Apply localized content for all stores
6. Set up currency conversion rates and catalog price scope

## Product Count Summary

- **Simple Products**: 20 SKUs
- **Configurable Products**: 5 parent products
- **Configurable Variations**: 30+ child products
- **Total SKUs**: 55+ unique products
- **Localized Content**: 4 languages (EN, FR, IT, IE)
- **Product Types**: Simple and Configurable only (simplified approach)

## Key Improvements

### Simplified Currency Management
- **Base Currency**: USD pricing with automatic conversion
- **No CSV-based pricing**: Removed complex currency_pricing.csv approach
- **Automatic conversion**: Magento handles currency conversion based on exchange rates
- **Website-level pricing**: Catalog price scope set to Website level

### Enhanced Configurable Products
- **Proper associations**: Configurable products are correctly linked to their variations
- **Automatic options**: Size and color options are automatically created
- **Simplified approach**: Only configurable products (no bundles or grouped products)

### Streamlined Product Types
- **Simple Products**: Individual sportswear items
- **Configurable Products**: Products with size/color variations
- **Removed complexity**: No bundle or grouped products for easier management

## Quality Assurance

All products include:
- Proper SEO metadata
- Stock management
- Category assignments
- Attribute configurations
- Localized content
- URL keys
- Meta descriptions and keywords
- Automatic currency conversion
- Proper configurable product associations

This catalog provides a comprehensive foundation for a sportswear e-commerce store with multi-language support, simplified currency management, and proper configurable product functionality.
