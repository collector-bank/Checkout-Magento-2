define([
    'uiComponent',
    'jquery',
], function (Element, $) {
    'use strict';
    return Element.extend({
        initialize: function (config) {
            this._super();
        },

        getNewsletterUrl: function() {
            return window.checkoutConfig.payment.collector_checkout.newsletter_url;
        },

        newsletterSubscribe: function() {
            var self = this;

            return function() {

                $.ajax({
                    url: self.getNewsletterUrl(),
                    data: {'subscribe': $('#checkout-newsletter-subscribe-checkbox').is(":checked")},
                    type: 'post',
                    dataType: 'json',
                    context: this
                });
            };
        }
    });
});
