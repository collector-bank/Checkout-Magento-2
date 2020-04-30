var config = {
    map: {
        '*': {
            collectorCheckout: 'Webbhuset_CollectorCheckout/js/checkout',
            collectorNewsletter: 'Webbhuset_CollectorCheckout/js/newsletter',
        }
    },
    config: {
        mixins: {
            'Magento_Checkout/js/action/get-payment-information': {
                'Webbhuset_CollectorCheckout/js/action/suspend-wrapper': true
            },
            'Magento_Checkout/js/action/set-shipping-information' : {
                'Webbhuset_CollectorCheckout/js/action/suspend-wrapper': true
            }
        }
    }
};
