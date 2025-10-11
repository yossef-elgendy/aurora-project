<?php
/**
 * Copyright © Escooter. All rights reserved.
 * See LICENSE.txt for license details.
 */

namespace Escooter\Checkout\Observer;

use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Sales\Model\Order\Invoice;
use Psr\Log\LoggerInterface;

/**
 * Observer to copy escooter_notes from order to invoice
 */
class CopyEscooterNotesToInvoice implements ObserverInterface
{
    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * Constructor
     *
     * @param LoggerInterface $logger
     */
    public function __construct(
        LoggerInterface $logger
    ) {
        $this->logger = $logger;
    }

    /**
     * Copy escooter_notes from order to invoice
     *
     * @param Observer $observer
     * @return void
     */
    public function execute(Observer $observer)
    {
        try {
            /** @var Invoice $invoice */
            $invoice = $observer->getEvent()->getInvoice();
            $order = $invoice->getOrder();

            if ($order && $order->getShippingAddress()) {
                $escooterNotes = $order->getShippingAddress()->getEscooterNotes();
                
                if ($escooterNotes) {
                    $invoice->setEscooterNotes($escooterNotes);
                }
            }
        } catch (\Exception $e) {
            $this->logger->error('Error copying escooter notes to invoice: ' . $e->getMessage());
        }
    }
}

