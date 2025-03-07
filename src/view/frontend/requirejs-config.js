var config = {
    map: {
        '*': {
            collectorCheckout: 'Webbhuset_CollectorCheckout/js/checkout',
            collectorNewsletter: 'Webbhuset_CollectorCheckout/js/newsletter',
            collectorIframe: "Webbhuset_CollectorCheckout/js/iframeLoader",
            collectorSuccess: "Webbhuset_CollectorCheckout/js/success",
            'Magento_Checkout/js/action/get-payment-information':
                'Webbhuset_CollectorCheckout/js/action/get-payment-information-override'
        }
    },
    config: {
        mixins: {
            'Magento_Checkout/js/action/set-shipping-information' : {
                'Webbhuset_CollectorCheckout/js/action/suspend-wrapper': true
            },
            'Magento_Checkout/js/model/cart/totals-processor/default' : {
                'Webbhuset_CollectorCheckout/js/action/suspend-wrapper-estimate-totals': true
            }
        }
    },
    shim: {
        'Webbhuset_CollectorCheckout/js/iframeLoader': {
            deps: ['Webbhuset_CollectorCheckout/js/checkout']
        }
    }
};
