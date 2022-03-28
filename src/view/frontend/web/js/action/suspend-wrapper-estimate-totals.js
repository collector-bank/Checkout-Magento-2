define([
    'mage/utils/wrapper',
    'Webbhuset_CollectorCheckout/js/iframe'
], function (wrapper, collectorIframe) {
    'use strict';

    return function (totalsDefaultProvider) {
        totalsDefaultProvider.estimateTotals = wrapper.wrapSuper(totalsDefaultProvider.estimateTotals, function (address) {
            if (!window.collector) {
                return this._super(address);
            }
            return this._super(address).done(function () {
                collectorIframe.resume();
            });
        });
        return totalsDefaultProvider;
    };
});
