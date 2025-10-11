/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 * Cache bust: 2024-01-07-15-30-00
 */

define([
    'jquery',
    'ko',
    'uiComponent',
    'Magento_Checkout/js/model/quote',
    'Magento_Checkout/js/checkout-data'
], function ($, ko, Component, quote, checkoutData) {
    'use strict';

    return Component.extend({
        defaults: {
            template: 'Escooter_Checkout/escooter-notes',
            escooterNotes: ko.observable('')
        },

        initialize: function () {
            this._super();
            var self = this;

            this.escooterNotes.subscribe(function (newValue) {
                var shippingAddress = quote.shippingAddress();
                var checkoutShippingAddress = checkoutData.getShippingAddressFromData();

                if (shippingAddress) {
                    if (!shippingAddress.extension_attributes) {
                        shippingAddress.extension_attributes = {};
                    }

                    shippingAddress.extension_attributes.escooter_notes = newValue;
                    quote.shippingAddress(shippingAddress);
                }

                if (checkoutShippingAddress) {
                    if (!checkoutShippingAddress.extension_attributes) {
                        checkoutShippingAddress.extension_attributes = {};
                    }

                    checkoutShippingAddress.extension_attributes.escooter_notes = newValue;
                    checkoutData.setShippingAddressFromData(checkoutShippingAddress);
                }
            });

            var shippingAddress = quote.shippingAddress();
            var checkoutShippingAddress = checkoutData.getShippingAddressFromData();

            if (shippingAddress && shippingAddress.extension_attributes && shippingAddress.extension_attributes.escooter_notes) {
                this.escooterNotes(shippingAddress.extension_attributes.escooter_notes);

                return;
            }

            if (checkoutShippingAddress && checkoutShippingAddress.extension_attributes && checkoutShippingAddress.extension_attributes.escooter_notes) {
                this.escooterNotes(checkoutShippingAddress.extension_attributes.escooter_notes);
            }
        }
    });
});
