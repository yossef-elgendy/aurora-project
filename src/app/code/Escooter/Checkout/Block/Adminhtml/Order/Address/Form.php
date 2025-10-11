<?php
/**
 * Copyright © Escooter. All rights reserved.
 * See LICENSE.txt for license details.
 */

namespace Escooter\Checkout\Block\Adminhtml\Order\Address;

use Magento\Sales\Block\Adminhtml\Order\Address\Form as OriginalForm;

/**
 * Extended Order Address Form to add escooter_notes field
 */
class Form extends OriginalForm
{
    /**
     * Add escooter_notes field to the form
     *
     * @return $this
     */
    protected function _prepareForm()
    {
        parent::_prepareForm();

        $form = $this->getForm();

        if ($form) {
            $fieldset = $form->getElement('main');

            if ($fieldset) {
                // Get current address
                $address = $this->_getAddress();

                // Add escooter_notes field after telephone
                $escooterNotesField = $fieldset->addField(
                    'escooter_notes',
                    'textarea',
                    [
                        'name' => 'escooter_notes',
                        'label' => __('Escooter Notes'),
                        'title' => __('Escooter Notes'),
                        'required' => false,
                        'rows' => 3,
                        'cols' => 30,
                        'value' => $address->getEscooterNotes(), // Set existing value
                    ],
                    'telephone'
                );
            }
        }

        return $this;
    }
}

