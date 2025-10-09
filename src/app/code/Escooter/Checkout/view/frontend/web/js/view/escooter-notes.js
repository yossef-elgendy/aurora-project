/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 * Cache bust: 2024-01-07-15-30-00
 */

define([
    'jquery',
    'ko',
    'uiComponent',
    'Magento_Checkout/js/model/quote'
], function ($, ko, Component, quote) {
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
                if (shippingAddress) {
                    if (!shippingAddress.extension_attributes) {
                        shippingAddress.extension_attributes = {};
                    }
                    shippingAddress.extension_attributes.escooter_notes = newValue;
                    quote.shippingAddress(shippingAddress);
                }
            });

            var shippingAddress = quote.shippingAddress();
            if (shippingAddress && shippingAddress.extension_attributes && shippingAddress.extension_attributes.escooter_notes) {
                this.escooterNotes(shippingAddress.extension_attributes.escooter_notes);
            }
        }
    });
});
