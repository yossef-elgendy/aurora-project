# Escooter ERP Connector Module

A comprehensive Magento 2 module for synchronizing orders to an external ERP system with robust retry logic, idempotency, and webhook support.

> **⚠️ MOCKUP MODULE**: This is a mockup implementation with no actual ERP connection. All functionality can be tested using the built-in mock endpoints. Perfect for testing, development, and demonstration purposes.

## Features

- ✅ Automatic order sync to ERP when invoice is created
- ✅ Configurable immediate or queued sync
- ✅ Hourly cron job for processing pending syncs
- ✅ Exponential backoff retry logic
- ✅ Idempotency key support
- ✅ HMAC signature verification
- ✅ Webhook endpoint for ERP callbacks
- ✅ Comprehensive logging and events
- ✅ REST API endpoints for manual sync operations
- ✅ Mock ERP endpoint for testing

## Installation

1. Copy the module to `app/code/Escooter/ErpConnector`

2. Enable the module:
```bash
bin/magento module:enable Escooter_ErpConnector
bin/magento setup:upgrade
bin/magento setup:di:compile
bin/magento cache:flush
```

## Configuration

Navigate to: **Stores > Configuration > Escooter > ERP Connector**

### General Settings
- **Enable ERP Connector**: Enable/disable the module
- **Immediate Sync on Invoice**: Sync immediately when invoice is created (yes) or queue for cron (no)
- **Debug Mode**: Enable detailed logging

### Cron Settings
- **Enable Cron Sync**: Enable automatic hourly sync
- **Cron Schedule**: Cron expression (default: `0 * * * *` for every hour)

### ERP API Configuration
- **ERP API Base URL**: Base URL for ERP API (e.g., `https://erp.example.com/api`)
- **ERP API Key**: API key for authentication
- **HMAC Secret**: Shared secret for HMAC signature validation
- **API Timeout**: HTTP request timeout in seconds (default: 30)

### Retry Configuration
- **Max Attempts**: Maximum retry attempts before marking as failed (default: 5)
- **Base Delay**: Base delay in seconds for exponential backoff (default: 60)

## Database Schema

### Table: `vendor_erp_sync`
Stores sync records with full audit trail:
- `sync_id` - Primary key
- `order_id` - Foreign key to sales_order
- `order_increment_id` - Human-readable order ID
- `status` - Sync status (pending, queued, in_progress, success, failed)
- `attempts` - Number of sync attempts
- `max_attempts` - Maximum allowed attempts
- `last_attempt_at` - Timestamp of last attempt
- `next_attempt_at` - Scheduled next attempt time
- `last_error` - Error message from last failed attempt
- `erp_reference` - Reference ID from ERP system
- `idempotency_key` - Unique key for idempotent requests
- `payload` - JSON payload sent to ERP
- `response` - Last response from ERP
- `created_at` - Record creation timestamp
- `updated_at` - Record update timestamp

### Column: `sales_order.erp_synced`
Boolean flag (0/1) indicating if order has been synced to ERP.

## REST API Endpoints

All endpoints require authentication token except webhook and mock endpoints.

### 1. Sync Order
```bash
POST /rest/V1/erpconnector/sync-order
Authorization: Bearer <admin_token>
Content-Type: application/json

{
  "orderIncrementId": "000000123"
}
```

**Response:**
```json
{
  "success": true,
  "sync_id": 1,
  "order_increment_id": "000000123",
  "status": "success",
  "message": "Order synced successfully",
  "erp_reference": "ERP-123",
  "attempts": 1,
  "last_error": null
}
```

### 2. Resync Order
```bash
POST /rest/V1/erpconnector/resync-order
Authorization: Bearer <admin_token>
Content-Type: application/json

{
  "orderIncrementId": "000000123"
}
```

### 3. Get Sync Status
```bash
GET /rest/V1/erpconnector/sync-status/000000123
Authorization: Bearer <admin_token>
```

**Response:**
```json
{
  "sync_id": 1,
  "order_id": 123,
  "order_increment_id": "000000123",
  "status": "success",
  "attempts": 1,
  "max_attempts": 5,
  "last_attempt_at": "2024-01-15 10:30:00",
  "next_attempt_at": null,
  "erp_reference": "ERP-123",
  "last_error": null,
  "created_at": "2024-01-15 10:29:00",
  "updated_at": "2024-01-15 10:30:00"
}
```

### 4. Webhook (ERP Callback)
```bash
POST /rest/V1/erpconnector/webhook
Content-Type: application/json
X-Signature: <hmac_signature>

{
  "orderIncrementId": "000000123",
  "erpReference": "ERP-123",
  "status": "accepted"
}
```

### 5. Mock ERP Endpoint (Testing)
```bash
POST /rest/V1/erpconnector/mock-update-stock
Content-Type: application/json

{
  "items": [
    {"sku": "PROD-001", "qty": 5}
  ],
  "orderIncrementId": "000000123",
  "idempotencyKey": "ERP_abc123"
}
```

## ERP Integration

### Outbound Request Format

The module sends POST requests to: `{erp_api_base_url}/orders/sync`

**Headers:**
```
Content-Type: application/json
X-API-KEY: <configured_api_key>
X-Idempotency-Key: <unique_key>
X-Signature: <hmac_sha256_signature>
```

**Payload:**
```json
{
  "order_increment_id": "000000123",
  "order_id": 123,
  "customer_email": "customer@example.com",
  "customer_firstname": "John",
  "customer_lastname": "Doe",
  "items": [
    {
      "sku": "PROD-001",
      "name": "Product Name",
      "qty": 2,
      "price": 99.99,
      "row_total": 199.98
    }
  ],
  "totals": {
    "subtotal": 199.98,
    "tax": 20.00,
    "shipping": 10.00,
    "discount": 0.00,
    "grand_total": 229.98
  },
  "billing_address": { ... },
  "shipping_address": { ... },
  "created_at": "2024-01-15 10:00:00",
  "updated_at": "2024-01-15 10:00:00"
}
```

### Expected ERP Response

**Success (2xx):**
```json
{
  "erp_reference": "ERP-123",
  "status": "accepted"
}
```

**Error (4xx/5xx):**
```json
{
  "error": "Error message",
  "code": "ERROR_CODE"
}
```

### Response Handling

- **2xx**: Marked as success
- **4xx (except 429)**: Non-retryable error, marked as failed
- **429**: Rate limited, retryable
- **5xx**: Server error, retryable
- **0/Timeout**: Network error, retryable

## Retry Logic

The module uses exponential backoff for retries:

```
delay = (2 ^ attempts) × base_delay_seconds
```

**Example with base_delay = 60 seconds:**
- Attempt 1: Immediate
- Attempt 2: After 2 minutes (2^1 × 60)
- Attempt 3: After 4 minutes (2^2 × 60)
- Attempt 4: After 8 minutes (2^3 × 60)
- Attempt 5: After 16 minutes (2^4 × 60)

After max attempts, the sync is marked as failed and no further retries occur.

## Events

The module dispatches events for custom logic:

### `vendor_erp_sync_before_send`
Fired before sending to ERP
- `sync` - Sync model
- `order` - Order object
- `payload` - Payload array

### `vendor_erp_sync_after_send`
Fired after ERP response
- `sync` - Sync model
- `order` - Order object
- `response` - Response object

### `vendor_erp_sync_success`
Fired on successful sync
- `sync` - Sync model
- `order` - Order object
- `response` - Response object

### `vendor_erp_sync_failed`
Fired when sync fails permanently
- `sync` - Sync model

## Cron Job

The cron job runs on the configured schedule (default: hourly).

**Manual execution:**
```bash
bin/magento cron:run --group default
```

The cron processes up to 100 pending records per execution to prevent timeouts.

## Logging

Logs are written to `var/log/system.log` with context:

- Order sync requests and responses
- Retry attempts and failures
- Webhook processing
- Error details with stack traces

Enable **Debug Mode** in configuration for verbose logging.

## Security

### HMAC Signature

Outbound requests include HMAC-SHA256 signature in `X-Signature` header:
```
signature = base64(hmac_sha256(payload, hmac_secret))
```

Webhook requests should be validated similarly.

### API Key Authentication

All ERP requests include `X-API-KEY` header with configured key.

### ACL Resources

- `Escooter_ErpConnector::config` - Access configuration
- `Escooter_ErpConnector::manage` - Manage sync operations
- `Escooter_ErpConnector::sync` - Sync orders
- `Escooter_ErpConnector::resync` - Resync orders
- `Escooter_ErpConnector::view_status` - View sync status

## Testing

### 1. Test with Mock Endpoint

Configure ERP API Base URL to point to your Magento instance:
```
https://your-magento.test/rest/V1/erpconnector
```

The mock endpoint will accept requests and log them.

### 2. Create Test Order

```bash
# Create an order and invoice it
# The module will automatically sync to ERP
```

### 3. Check Sync Status

```bash
curl -X GET "https://magento.test/rest/V1/erpconnector/sync-status/000000123" \
  -H "Authorization: Bearer YOUR_ADMIN_TOKEN"
```

### 4. View Logs

```bash
tail -f var/log/system.log | grep "ERP"
```

## Troubleshooting

### Order not syncing

1. Check module is enabled in configuration
2. Verify cron is running: `bin/magento cron:run`
3. Check logs: `var/log/system.log`
4. Verify ERP API URL is accessible

### Sync stuck in "pending" status

1. Check cron schedule is valid
2. Ensure cron is enabled in configuration
3. Run cron manually: `bin/magento cron:run`
4. Check `next_attempt_at` timestamp

### Authentication errors

1. Verify ERP API Key is correct
2. Check HMAC secret matches ERP configuration
3. Review ERP endpoint logs

## Architecture

### Service Layer
- `ErpSyncService` - Core sync logic
- `ErpClient` - HTTP client for ERP communication

### Repository Pattern
- `SyncRepository` - CRUD operations for sync records

### Observer Pattern
- `InvoiceCreatedObserver` - Triggers sync on invoice creation

### Dependency Injection
All services use constructor injection for testability.

### Events
Dispatched at key lifecycle points for extensibility.

## License

Copyright © Escooter. All rights reserved.

## Support

For issues or questions, please contact your development team.

