# Escooter Checkout Module

This module adds a custom field called `escooter_notes` to the Magento 2 checkout process.

## Features

- Adds a textarea field "Escooter Notes" to the shipping step of the checkout process
- Saves the field value in the quote during checkout
- Transfers the field value from quote to order when the order is placed
- Stores the field value in the database for both quote addresses and orders

## Installation

1. Copy the module files to `app/code/Escooter/Checkout/`
2. Run the following commands:
   ```bash
   php bin/magento module:enable Escooter_Checkout
   php bin/magento setup:upgrade
   php bin/magento setup:di:compile
   php bin/magento cache:flush
   ```

## Usage

The escooter_notes field will appear as a textarea in the shipping step of the checkout process. Customers can enter notes related to their escooter order, and this information will be saved with the order.

## Technical Details

- Uses Magento 2's LayoutProcessor plugin to add the custom field
- Implements extension attributes for both quote addresses and orders
- Uses observers to transfer data from quote to order
- Includes database schema changes to store the field values

## Files Structure

```
app/code/Escooter/Checkout/
├── Plugin/
│   ├── Checkout/
│   │   └── LayoutProcessor.php
│   ├── Quote/
│   │   └── AddressPlugin.php
│   └── Sales/
│       └── OrderPlugin.php
├── Model/
│   ├── Quote/
│   │   └── AddressExtension.php
│   └── Sales/
│       └── OrderExtension.php
├── Observer/
│   └── SaveEscooterNotes.php
├── Setup/
│   └── Patch/
│       └── Data/
│           └── AddEscooterNotesColumns.php
├── etc/
│   ├── di.xml
│   ├── events.xml
│   ├── extension_attributes.xml
│   ├── module.xml
│   └── db_schema.xml
├── registration.php
├── composer.json
└── README.md
```
