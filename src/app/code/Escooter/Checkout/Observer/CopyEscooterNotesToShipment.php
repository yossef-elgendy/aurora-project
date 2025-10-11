<?php
/**
 * Copyright © Escooter. All rights reserved.
 * See LICENSE.txt for license details.
 */

namespace Escooter\Checkout\Observer;

use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Sales\Model\Order\Shipment;
use Psr\Log\LoggerInterface;

/**
 * Observer to copy escooter_notes from order to shipment
 */
class CopyEscooterNotesToShipment implements ObserverInterface
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
     * Copy escooter_notes from order to shipment
     *
     * @param Observer $observer
     * @return void
     */
    public function execute(Observer $observer)
    {
        try {
            /** @var Shipment $shipment */
            $shipment = $observer->getEvent()->getShipment();
            $order = $shipment->getOrder();

            if ($order && $order->getShippingAddress()) {
                $escooterNotes = $order->getShippingAddress()->getEscooterNotes();
                
                if ($escooterNotes) {
                    $shipment->setEscooterNotes($escooterNotes);
                }
            }
        } catch (\Exception $e) {
            $this->logger->error('Error copying escooter notes to shipment: ' . $e->getMessage());
        }
    }
}

