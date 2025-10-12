# Escooter_ErpConnector Testing Guide

This module is a **mockup** with no actual ERP connection. All tests verify functionality using mock data and endpoints.

## Test Structure

```
Test/
├── Unit/                           # Unit tests (no Magento dependencies)
│   ├── Model/
│   │   ├── ErpClientTest.php      # HTTP client tests
│   │   └── ErpResponseTest.php    # Response handling tests
│   └── Helper/
│       └── IdempotencyKeyGeneratorTest.php  # Key generation tests
├── Integration/                    # Integration tests (with Magento framework)
│   ├── Model/
│   │   └── SyncRepositoryTest.php # Database operations
│   ├── Api/
│   │   └── SyncManagementTest.php # REST API endpoints
│   ├── DbSchemaTest.php           # Database schema validation
│   └── EndToEndSyncTest.php       # Complete flow tests
└── phpunit.xml                     # PHPUnit configuration
```

## Running Tests

### Prerequisites

1. **Install Magento test framework:**
```bash
cd /Users/yossefsherif/Desktop/Work/aurora-project
composer install
```

2. **Configure test database** (if not already configured):
```bash
# Edit dev/tests/integration/etc/install-config-mysql.php with test DB credentials
```

3. **Enable the module:**
```bash
bin/magento module:enable Escooter_ErpConnector
bin/magento setup:upgrade
```

### Run All Tests

```bash
# From project root
bin/magento dev:tests:run
```

### Run Unit Tests Only

```bash
# Run all unit tests
vendor/bin/phpunit -c src/app/code/Escooter/ErpConnector/Test/phpunit.xml --testsuite Escooter_ErpConnector_Unit_Tests

# Run specific unit test
vendor/bin/phpunit src/app/code/Escooter/ErpConnector/Test/Unit/Model/ErpClientTest.php
```

### Run Integration Tests Only

```bash
# Run all integration tests
vendor/bin/phpunit -c dev/tests/integration/phpunit.xml src/app/code/Escooter/ErpConnector/Test/Integration/

# Run specific integration test
vendor/bin/phpunit -c dev/tests/integration/phpunit.xml src/app/code/Escooter/ErpConnector/Test/Integration/DbSchemaTest.php
```

### Run with Coverage

```bash
vendor/bin/phpunit -c src/app/code/Escooter/ErpConnector/Test/phpunit.xml --coverage-html coverage/
```

## Test Coverage

### Unit Tests

#### ✅ ErpClientTest.php
Tests HTTP client functionality:
- ✓ Successful order sync request
- ✓ Exception handling when base URL not configured
- ✓ Network error handling
- ✓ HMAC signature generation
- ✓ Connection testing

#### ✅ ErpResponseTest.php
Tests response parsing:
- ✓ Success/failure status detection
- ✓ Status code validation
- ✓ JSON parsing
- ✓ ERP ID extraction from multiple fields
- ✓ Error message extraction
- ✓ Retry logic (2xx, 4xx, 5xx, 429, network errors)

#### ✅ IdempotencyKeyGeneratorTest.php
Tests unique key generation:
- ✓ Key format with prefix
- ✓ Unique keys for different orders
- ✓ Consistent keys for same order
- ✓ Generation from increment ID

### Integration Tests

#### ✅ DbSchemaTest.php
Validates database schema:
- ✓ `vendor_erp_sync` table exists
- ✓ All required columns present
- ✓ Indexes created properly
- ✓ `sales_order.erp_synced` column exists
- ✓ Foreign key constraints

#### ✅ SyncRepositoryTest.php
Tests database operations:
- ✓ Save and retrieve sync records
- ✓ Get by order ID
- ✓ Get by order increment ID
- ✓ Get by idempotency key
- ✓ List with filters
- ✓ Update records
- ✓ Delete records

#### ✅ SyncManagementTest.php
Tests REST API endpoints:
- ✓ Mock stock update endpoint
- ✓ Sync status retrieval
- ✓ Webhook processing
- ✓ Direct service calls

#### ✅ EndToEndSyncTest.php
Tests complete workflows:
- ✓ Full sync flow (create → pending → success)
- ✓ Multiple identifier lookups
- ✓ List filtering by status
- ✓ Mock ERP endpoint integration
- ✓ Retry attempt tracking

## Manual Testing

### 1. Test Mock ERP Endpoint

```bash
curl -X POST "http://localhost/magento/rest/V1/erpconnector/mock-update-stock" \
  -H "Content-Type: application/json" \
  -d '{
    "items": [
      {"sku": "TEST-001", "qty": 5}
    ],
    "orderIncrementId": "000000123",
    "idempotencyKey": "test-key-123"
  }'
```

**Expected Response:**
```json
{
  "ok": true,
  "message": "Stock updated successfully (mock)",
  "order_increment_id": "000000123",
  "idempotency_key": "test-key-123",
  "erp_reference": "ERP-xxxxx",
  "items": [
    {"sku": "TEST-001", "qty": 5, "status": "updated"}
  ],
  "timestamp": "2024-01-15 10:30:00"
}
```

### 2. Test Sync Status (No Auth Required for Testing)

```bash
# Create a test sync record first via database or API
# Then query status:

curl -X GET "http://localhost/magento/rest/V1/erpconnector/sync-status/000000123" \
  -H "Authorization: Bearer YOUR_ADMIN_TOKEN"
```

### 3. Test Database Records

```sql
-- Check sync records
SELECT sync_id, order_increment_id, status, attempts, erp_reference, created_at 
FROM vendor_erp_sync 
ORDER BY created_at DESC 
LIMIT 10;

-- Check order sync flags
SELECT entity_id, increment_id, erp_synced, created_at 
FROM sales_order 
WHERE erp_synced = 1 
LIMIT 10;

-- Find pending syncs
SELECT * FROM vendor_erp_sync 
WHERE status IN ('pending', 'queued') 
AND (next_attempt_at <= NOW() OR next_attempt_at IS NULL);
```

### 4. Test Configuration

```bash
# Check if module is enabled
bin/magento config:show erpconnector/general/enabled

# Set test configuration
bin/magento config:set erpconnector/general/enabled 1
bin/magento config:set erpconnector/erp_api/base_url "http://localhost/magento/rest/V1/erpconnector"
bin/magento config:set erpconnector/retry/max_attempts 3

# Flush cache
bin/magento cache:flush
```

### 5. Test Cron Job

```bash
# Run cron manually
bin/magento cron:run

# Check cron logs
tail -f var/log/system.log | grep "ERP Sync Cron"

# View scheduled cron jobs
bin/magento cron:status | grep erpconnector
```

## Testing Scenarios

### Scenario 1: Create Sync Record
```php
// In Magento console or script
$objectManager = \Magento\Framework\App\ObjectManager::getInstance();
$syncFactory = $objectManager->get(\Escooter\ErpConnector\Model\SyncFactory::class);
$syncRepository = $objectManager->get(\Escooter\ErpConnector\Api\SyncRepositoryInterface::class);

$sync = $syncFactory->create();
$sync->setOrderId(999);
$sync->setOrderIncrementId('100000999');
$sync->setStatus('pending');
$sync->setIdempotencyKey('TEST-KEY-999');
$syncRepository->save($sync);

echo "Sync created with ID: " . $sync->getSyncId();
```

### Scenario 2: Simulate Sync Processing
```php
$syncService = $objectManager->get(\Escooter\ErpConnector\Model\ErpSyncService::class);
$syncRepository = $objectManager->get(\Escooter\ErpConnector\Api\SyncRepositoryInterface::class);

$sync = $syncRepository->getByOrderIncrementId('100000999');
$result = $syncService->processSync($sync);

echo $result ? "Success" : "Failed";
```

### Scenario 3: Test Idempotency
```bash
# Send same request twice
for i in {1..2}; do
  curl -X POST "http://localhost/magento/rest/V1/erpconnector/mock-update-stock" \
    -H "Content-Type: application/json" \
    -d '{
      "items": [{"sku": "TEST", "qty": 1}],
      "orderIncrementId": "SAME-ORDER",
      "idempotencyKey": "SAME-KEY"
    }'
  echo "\nRequest $i sent"
  sleep 1
done
```

### Scenario 4: Test Retry Logic
```php
// Simulate failed attempts
$sync = $syncRepository->getByOrderIncrementId('100000999');

for ($i = 1; $i <= 3; $i++) {
    $sync->setAttempts($i);
    $sync->setStatus('failed');
    $sync->setLastError('Simulated error #' . $i);
    
    // Calculate next attempt with exponential backoff
    $delay = pow(2, $i) * 60;
    $nextAttempt = date('Y-m-d H:i:s', time() + $delay);
    $sync->setNextAttemptAt($nextAttempt);
    
    $syncRepository->save($sync);
    echo "Attempt $i: Next retry at $nextAttempt\n";
}
```

## Verification Checklist

- [ ] All unit tests pass
- [ ] All integration tests pass
- [ ] Database schema created correctly
- [ ] Sync records can be created and retrieved
- [ ] Mock ERP endpoint responds correctly
- [ ] Idempotency keys are unique and consistent
- [ ] Retry logic calculates backoff properly
- [ ] Status transitions work (pending → queued → success/failed)
- [ ] Multiple retrieval methods work (by ID, increment ID, idempotency key)
- [ ] API endpoints respond with correct data
- [ ] Configuration values are respected
- [ ] Logs are written properly

## Troubleshooting Tests

### Tests Fail with Database Errors
```bash
# Reset test database
bin/magento setup:upgrade
bin/magento setup:db-schema:upgrade

# Clear generated code
rm -rf generated/code/*
bin/magento setup:di:compile
```

### Integration Tests Don't Run
```bash
# Ensure integration test config exists
ls -la dev/tests/integration/etc/

# Run with verbose output
vendor/bin/phpunit -c dev/tests/integration/phpunit.xml --verbose src/app/code/Escooter/ErpConnector/Test/Integration/
```

### Mock Endpoint Returns 404
```bash
# Verify module is enabled
bin/magento module:status Escooter_ErpConnector

# Regenerate webapi config
bin/magento setup:upgrade
bin/magento cache:flush
```

## Test Data Cleanup

```sql
-- Clean up test sync records
DELETE FROM vendor_erp_sync WHERE order_increment_id LIKE '1000%';

-- Reset order sync flags
UPDATE sales_order SET erp_synced = 0 WHERE erp_synced = 1;
```

## Continuous Integration

Example `.github/workflows/tests.yml`:
```yaml
name: Run Tests

on: [push, pull_request]

jobs:
  test:
    runs-on: ubuntu-latest
    steps:
      - uses: actions/checkout@v2
      
      - name: Setup PHP
        uses: shivammathur/setup-php@v2
        with:
          php-version: '8.1'
      
      - name: Install dependencies
        run: composer install
      
      - name: Run unit tests
        run: vendor/bin/phpunit -c src/app/code/Escooter/ErpConnector/Test/phpunit.xml --testsuite Escooter_ErpConnector_Unit_Tests
      
      - name: Run integration tests
        run: vendor/bin/phpunit -c dev/tests/integration/phpunit.xml src/app/code/Escooter/ErpConnector/Test/Integration/
```

## Test Metrics

Expected test execution times:
- **Unit Tests**: ~2-5 seconds
- **Integration Tests**: ~30-60 seconds
- **End-to-End Tests**: ~45-90 seconds

Expected coverage:
- **Line Coverage**: >80%
- **Method Coverage**: >90%
- **Class Coverage**: 100%

## Summary

This testing suite provides comprehensive coverage of:
- ✅ Unit-level business logic
- ✅ Database operations and schema
- ✅ API endpoints functionality
- ✅ Complete sync workflows
- ✅ Error handling and retry logic
- ✅ Configuration management
- ✅ Idempotency and security

All tests use **mock data** and the **internal mock ERP endpoint** - no external systems required!

