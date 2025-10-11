<?php
/**
 * Copyright © Escooter. All rights reserved.
 * See LICENSE.txt for license details.
 */

namespace Escooter\Checkout\Plugin\Model\Order\Address;

use Magento\Sales\Model\Order\Address;
use Magento\Sales\Model\Order\Address\Renderer;

/**
 * Plugin to add escooter_notes to formatted address
 */
class RendererPlugin
{
    /**
     * Add escooter notes to formatted address
     *
     * @param Renderer $subject
     * @param string|null $result
     * @param Address $address
     * @param string $type
     * @return string|null
     */
    public function afterFormat(
        Renderer $subject,
        $result,
        Address $address,
        $type
    ) {
        if ($result === null) {
            return $result;
        }

        $escooterNotes = $address->getEscooterNotes();

        if ($escooterNotes) {
            $result .= '<br/><strong>' . __('Notes') . ':</strong> ' . $escooterNotes;
        }

        return $result;
    }
}
