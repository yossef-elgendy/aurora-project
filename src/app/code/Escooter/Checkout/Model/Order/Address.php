<?php
/**
 * Copyright © Escooter. All rights reserved.
 * See LICENSE.txt for license details.
 */

namespace Escooter\Checkout\Model\Order;

use Magento\Sales\Model\Order\Address as OrderAddress;

/**
 * Extended Order Address model with escooter_notes support
 */
class Address extends OrderAddress
{
    /**
     * Get escooter notes
     *
     * @return string|null
     */
    public function getEscooterNotes()
    {
        return $this->getData('escooter_notes');
    }

    /**
     * Set escooter notes
     *
     * @param string|null $escooterNotes
     * @return $this
     */
    public function setEscooterNotes($escooterNotes)
    {
        return $this->setData('escooter_notes', $escooterNotes);
    }
}

