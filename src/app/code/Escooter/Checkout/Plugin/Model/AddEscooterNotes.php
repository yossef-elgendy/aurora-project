<?php
/**
 * Copyright © Escooter. All rights reserved.
 * See LICENSE.txt for license details.
 */

namespace Escooter\Checkout\Plugin\Model;

use Magento\Checkout\Model\ShippingInformationManagement;
use Magento\Checkout\Api\Data\ShippingInformationInterface;
use Magento\Quote\Api\Data\AddressInterface;
use Magento\Quote\Api\Data\AddressExtensionInterface;
use Magento\Quote\Model\Quote\Address;
use Magento\Quote\Model\QuoteRepository;
use Magento\Quote\Model\Quote;
use Psr\Log\LoggerInterface;

/**
 * Plugin to handle escooter_notes in shipping information
 */
class AddEscooterNotes
{
    /**
     * @var QuoteRepository
     */
    private $quoteRepository;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @param QuoteRepository $quoteRepository
     * @param LoggerInterface $logger
     */
    public function __construct(
        QuoteRepository $quoteRepository,
        LoggerInterface $logger
    ) {
        $this->quoteRepository = $quoteRepository;
        $this->logger = $logger;
    }

    /**
     * Before save address information
     *
     * @param ShippingInformationManagement $subject
     * @param int $cartId
     * @param ShippingInformationInterface $addressInformation
     * @return array
     */
    public function beforeSaveAddressInformation(
        ShippingInformationManagement $subject,
        $cartId,
        ShippingInformationInterface $addressInformation
    ) {
        try {
            /** @var Quote $quote */
            $quote = $this->quoteRepository->get($cartId);
            $shippingAddress = $addressInformation->getShippingAddress();

            if ($shippingAddress && $shippingAddress->getExtensionAttributes()) {
                $extensionAttributes = $shippingAddress->getExtensionAttributes();

                if ($extensionAttributes->getEscooterNotes() !== null) {
                    // Set the escooter_notes on the shipping address
                    if ($shippingAddress instanceof Address) {
                        $shippingAddress->setData('escooter_notes', $extensionAttributes->getEscooterNotes());
                    }

                    // Also set it on the quote's shipping address
                    $quoteShippingAddress = $quote->getShippingAddress();
                    if ($quoteShippingAddress) {
                        $quoteShippingAddress->setData('escooter_notes', $extensionAttributes->getEscooterNotes());
                    }
                }
            }
        } catch (\Exception $e) {
            $this->logger->error('Error saving escooter notes: ' . $e->getMessage());
        }

        return [$cartId, $addressInformation];
    }
}
