<?php
/**
 * Copyright © Escooter. All rights reserved.
 * See LICENSE.txt for license details.
 */

namespace Escooter\Checkout\Model\Quote;

use Magento\Quote\Model\Quote\Address as QuoteAddress;

/**
 * Extended Quote Address model with escooter_notes support
 */
class Address extends QuoteAddress
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
