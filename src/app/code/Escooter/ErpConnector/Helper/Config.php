<?php
/**
 * Copyright © Escooter. All rights reserved.
 */
declare(strict_types=1);

namespace Escooter\ErpConnector\Helper;

use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Framework\App\Helper\Context;
use Magento\Framework\Encryption\EncryptorInterface;
use Magento\Store\Model\ScopeInterface;

class Config extends AbstractHelper
{
    public const XML_PATH_GENERAL_ENABLED = 'erpconnector/general/enabled';
    public const XML_PATH_GENERAL_IMMEDIATE_SYNC = 'erpconnector/general/immediate_sync_on_invoice';
    public const XML_PATH_GENERAL_DEBUG = 'erpconnector/general/debug';

    public const XML_PATH_CRON_ENABLED = 'erpconnector/cron/enabled';
    public const XML_PATH_CRON_SCHEDULE = 'erpconnector/cron/schedule';

    public const XML_PATH_ERP_API_BASE_URL = 'erpconnector/erp_api/base_url';
    public const XML_PATH_ERP_API_KEY = 'erpconnector/erp_api/api_key';
    public const XML_PATH_ERP_API_HMAC_SECRET = 'erpconnector/erp_api/hmac_secret';
    public const XML_PATH_ERP_API_TIMEOUT = 'erpconnector/erp_api/timeout';

    public const XML_PATH_RETRY_MAX_ATTEMPTS = 'erpconnector/retry/max_attempts';
    public const XML_PATH_RETRY_BASE_DELAY = 'erpconnector/retry/base_delay_seconds';

    /**
     * @var EncryptorInterface
     */
    private $encryptor;

    /**
     * @param Context $context
     * @param EncryptorInterface $encryptor
     */
    public function __construct(
        Context $context,
        EncryptorInterface $encryptor
    ) {
        parent::__construct($context);
        $this->encryptor = $encryptor;
    }

    /**
     * Check if module is enabled
     *
     * @param int|null $storeId
     * @return bool
     */
    public function isEnabled(?int $storeId = null): bool
    {
        return $this->scopeConfig->isSetFlag(
            self::XML_PATH_GENERAL_ENABLED,
            ScopeInterface::SCOPE_STORE,
            $storeId
        );
    }

    /**
     * Check if immediate sync on invoice is enabled
     *
     * @param int|null $storeId
     * @return bool
     */
    public function isImmediateSyncEnabled(?int $storeId = null): bool
    {
        return $this->scopeConfig->isSetFlag(
            self::XML_PATH_GENERAL_IMMEDIATE_SYNC,
            ScopeInterface::SCOPE_STORE,
            $storeId
        );
    }

    /**
     * Check if debug mode is enabled
     *
     * @param int|null $storeId
     * @return bool
     */
    public function isDebugEnabled(?int $storeId = null): bool
    {
        return $this->scopeConfig->isSetFlag(
            self::XML_PATH_GENERAL_DEBUG,
            ScopeInterface::SCOPE_STORE,
            $storeId
        );
    }

    /**
     * Check if cron is enabled
     *
     * @param int|null $storeId
     * @return bool
     */
    public function isCronEnabled(?int $storeId = null): bool
    {
        return $this->scopeConfig->isSetFlag(
            self::XML_PATH_CRON_ENABLED,
            ScopeInterface::SCOPE_STORE,
            $storeId
        );
    }

    /**
     * Get cron schedule
     *
     * @param int|null $storeId
     * @return string
     */
    public function getCronSchedule(?int $storeId = null): string
    {
        return (string) $this->scopeConfig->getValue(
            self::XML_PATH_CRON_SCHEDULE,
            ScopeInterface::SCOPE_STORE,
            $storeId
        );
    }

    /**
     * Get ERP API base URL
     *
     * @param int|null $storeId
     * @return string
     */
    public function getErpApiBaseUrl(?int $storeId = null): string
    {
        return (string) $this->scopeConfig->getValue(
            self::XML_PATH_ERP_API_BASE_URL,
            ScopeInterface::SCOPE_STORE,
            $storeId
        );
    }

    /**
     * Get ERP API key
     *
     * @param int|null $storeId
     * @return string
     */
    public function getErpApiKey(?int $storeId = null): string
    {
        $value = $this->scopeConfig->getValue(
            self::XML_PATH_ERP_API_KEY,
            ScopeInterface::SCOPE_STORE,
            $storeId
        );
        return $value ? $this->encryptor->decrypt($value) : '';
    }

    /**
     * Get HMAC secret
     *
     * @param int|null $storeId
     * @return string
     */
    public function getHmacSecret(?int $storeId = null): string
    {
        $value = $this->scopeConfig->getValue(
            self::XML_PATH_ERP_API_HMAC_SECRET,
            ScopeInterface::SCOPE_STORE,
            $storeId
        );
        return $value ? $this->encryptor->decrypt($value) : '';
    }

    /**
     * Get API timeout in seconds
     *
     * @param int|null $storeId
     * @return int
     */
    public function getApiTimeout(?int $storeId = null): int
    {
        return (int) $this->scopeConfig->getValue(
            self::XML_PATH_ERP_API_TIMEOUT,
            ScopeInterface::SCOPE_STORE,
            $storeId
        );
    }

    /**
     * Get max retry attempts
     *
     * @param int|null $storeId
     * @return int
     */
    public function getMaxAttempts(?int $storeId = null): int
    {
        return (int) $this->scopeConfig->getValue(
            self::XML_PATH_RETRY_MAX_ATTEMPTS,
            ScopeInterface::SCOPE_STORE,
            $storeId
        );
    }

    /**
     * Get base delay in seconds
     *
     * @param int|null $storeId
     * @return int
     */
    public function getBaseDelay(?int $storeId = null): int
    {
        return (int) $this->scopeConfig->getValue(
            self::XML_PATH_RETRY_BASE_DELAY,
            ScopeInterface::SCOPE_STORE,
            $storeId
        );
    }
}
