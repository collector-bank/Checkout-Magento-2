define([
    'uiComponent',
    'jquery',
    'Magento_Customer/js/customer-data'
], function (Element, $, customerData) {
    'use strict';
    return Element.extend({
        initialize: function (config) {
            this._super();
            var sections = ['cart'];

            customerData.invalidate(sections);
            customerData.reload(sections, true);
        },
    });
});
