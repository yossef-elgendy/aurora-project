# Escooter Checkout Module

This module adds a custom field called `escooter_notes` to the Magento 2 checkout process.

## Features

- Adds a textarea field "Escooter Notes" to the shipping step of the checkout process
- Saves the field value in the quote during checkout
- Transfers the field value from quote to order when the order is placed
- Automatically copies the field to invoices, shipments, and credit memos
- Displays escooter_notes in all transactional emails (order, invoice, shipment, credit memo)
- Shows the field in the admin order view and edit screens
- Adds the field to the order grid in the admin panel
- Stores the field value in the database for quote addresses, orders, invoices, shipments, and credit memos

## Usage

The escooter_notes field will appear as a textarea in the shipping step of the checkout process. Customers can enter notes related to their escooter order, and this information will be saved with the order.

## Technical Details

- Uses Magento 2's LayoutProcessor plugin to add the custom field to checkout
- Implements extension attributes for quote addresses
- Uses fieldset.xml to transfer data from quote to order, and from order to invoice/shipment/creditmemo
- Uses observers to ensure data is copied to all sales documents
- Includes database schema changes to store the field values across all sales tables
- Custom email templates for all transactional emails that display the escooter_notes field
- Plugin system to add the field to order address rendering

## Files Structure

```
app/code/Escooter/Checkout/
├── Block/
│   └── Adminhtml/
│       └── Order/
│           └── Address/
│               └── Form.php
├── Model/
│   ├── Order/
│   │   └── Address.php
│   └── Quote/
│       └── Address.php
├── Observer/
│   ├── CopyEscooterNotesToInvoice.php
│   ├── CopyEscooterNotesToShipment.php
│   └── CopyEscooterNotesToCreditmemo.php
├── Plugin/
│   └── Model/
│       ├── AddEscooterNotes.php
│       ├── Order/
│       │   └── Address/
│       │       └── RendererPlugin.php
│       └── ResourceModel/
│           └── Order/
│               └── Grid/
│                   └── Collection.php
├── etc/
│   ├── db_schema.xml
│   ├── di.xml
│   ├── email_templates.xml
│   ├── events.xml
│   ├── extension_attributes.xml
│   ├── fieldset.xml
│   └── module.xml
├── view/
│   ├── adminhtml/
│   │   ├── layout/
│   │   ├── templates/
│   │   │   └── order/
│   │   │       └── view/
│   │   └── ui_component/
│   │       └── sales_order_grid.xml
│   └── frontend/
│       ├── email/
│       │   ├── order_new.html
│       │   ├── order_new_guest.html
│       │   ├── invoice_new.html
│       │   ├── invoice_new_guest.html
│       │   ├── shipment_new.html
│       │   ├── shipment_new_guest.html
│       │   ├── creditmemo_new.html
│       │   └── creditmemo_new_guest.html
│       ├── layout/
│       │   └── checkout_index_index.xml
│       ├── web/
│       │   ├── js/
│       │   │   └── view/
│       │   │       └── escooter-notes.js
│       │   └── template/
│       │       ├── billing-address/
│       │       │   └── details.html
│       │       └── escooter-notes.html
│       └── requirejs-config.js
├── registration.php
└── README.md
```

## Email Templates

The module includes custom email templates for all transactional emails that display the escooter_notes field when present:

- **Order Confirmation Emails**: `order_new.html`, `order_new_guest.html`
- **Invoice Emails**: `invoice_new.html`, `invoice_new_guest.html`
- **Shipment Emails**: `shipment_new.html`, `shipment_new_guest.html`
- **Credit Memo Emails**: `creditmemo_new.html`, `creditmemo_new_guest.html`

To use these templates, go to **Admin Panel > Stores > Configuration > Sales > Sales Emails** and select the custom templates for each email type.

## Database Schema

The `escooter_notes` field is added to the following tables:
- `quote_address`
- `sales_order_address`
- `sales_invoice`
- `sales_shipment`
- `sales_creditmemo`
