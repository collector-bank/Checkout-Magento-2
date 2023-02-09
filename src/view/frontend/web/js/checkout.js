define([
    'uiComponent',
    'jquery',
    'mage/storage',
    'Webbhuset_CollectorCheckout/js/iframe',
    'Magento_Checkout/js/model/quote',
    'Magento_Checkout/js/model/shipping-rate-registry',
    'Magento_Checkout/js/model/shipping-rate-processor/new-address',
    'Magento_Checkout/js/model/cart/totals-processor/default',
    'Magento_Checkout/js/model/cart/cache',
    'Magento_Customer/js/customer-data',
    'Magento_Checkout/js/checkout-data',
    'Magento_Ui/js/modal/confirm',
    'Magento_Ui/js/modal/alert',
], function (
        Element,
        $,
        storage,
        collectorIframe,
        quote,
        rateRegistry,
        defaultProcessor,
        totalsDefaultProvider,
        cartCache,
        customerData,
        checkoutData,
        confirm,
        alert
    ) {
    'use strict';
    return Element.extend({
        defaults: {
            template: 'Webbhuset_CollectorCheckout/checkout',
        },
        timeout: null,
        cartData: {},
        initialize: function (config) {
            var self = this;

            // Clear localstorage cache
            var localStorage = $.initNamespaceStorage('mage-cache-storage').localStorage;
            localStorage.remove('cart-data');
            localStorage.remove('checkout-data');
            localStorage.remove('cart');

            self.setCheckoutData();
            this.cartData = customerData.get('cart');

            // Reload page if we cannot use collector checkout
            quote.totals.subscribe(function (newTotals) {
                if (newTotals.grand_total == 0) {
                    window.location.reload(false);
                }
            });

            $(document).on('ajax:updateCartItemQty', function() {
                collectorIframe.suspend();
                self.fetchShippingRates();
            });

            $(document).on('ajax:removeFromCart', function() {
                collectorIframe.suspend();
                self.fetchShippingRates();
            });

            document.addEventListener('collectorCheckoutCustomerUpdated', self.listener.bind(self));
            document.addEventListener('collectorCheckoutOrderValidationFailed', self.listener.bind(self));
            document.addEventListener('collectorCheckoutLocked', self.listener.bind(self));
            document.addEventListener('collectorCheckoutUnlocked', self.listener.bind(self));
            document.addEventListener('collectorCheckoutReloadedByUser', self.listener.bind(self));
            document.addEventListener('collectorCheckoutExpired', self.listener.bind(self));
            document.addEventListener('collectorCheckoutResumed', self.listener.bind(self));
            document.addEventListener('collectorCheckoutShippingUpdated', self.listener.bind(self));

            var event = {type: "update", detail: window.checkoutConfig.quoteData.collectorbank_public_id}

            this._super();

        },
        listener: function(event) {
            switch(event.type) {
                case 'collectorCheckoutCustomerUpdated':
                    /*
                        Occurs when the checkout client-side detects any change to customer information,
                        such as a changed email, mobile phone number or delivery address.
                        This event is also fired the first time the customer is identified.
                    */
                    console.log("customer updated");
                    this.addressUpdated(event);
                    break;

                case 'collectorCheckoutShippingUpdated':
                    /*
                        Occurs when the checkout client-side detects any change to customer information,
                        such as a changed email, mobile phone number or delivery address.
                        This event is also fired the first time the customer is identified.
                    */
                    console.log("shipping updated");
                    this.addressUpdated(event);
                    break;


                case 'collectorCheckoutOrderValidationFailed':
                    /*
                        This event is only used if you use the optional validate order functionality.
                        Occurs if a purchase is denied due to the result of the backend order validation
                        (in other words if the response from the validationUri set at initialization is not successful).
                        This usually means that one or more items in the cart is no longer in stock.
                    */
                    break;

                case 'collectorCheckoutLocked':
                    this.lockCartForInput(true);
                    /*
                        Occurs when no user input should be accepted, for instance during processing of a purchase.
                    */
                    break;

                case 'collectorCheckoutUnlocked':
                    this.lockCartForInput(false);
                    /*
                        Occurs after a locked event when it is safe to allow user input again.
                        For instance after a purchase has been processed (regardless of whether the purchase was successful or not).
                    */
                    var url = this.getReinitUrl();
                    var data = { publicId: event.detail };
                    $.ajax({
                        url: url,
                        data: data,
                        type: 'post',
                        dataType: 'json',
                        context: this,
                    })
                    .fail(function(response) {
                        console.error(response);
                    });
                    break;

                case 'collectorCheckoutReloadedByUser':
                    location.reload();
                    /*
                        Occurs when the user has clicked a "reload" button in the checkout.
                        This can occur when there is a version mismatch in the checkout.
                        An example is when adding an item to the cart and before calling suspend/resume trying to set an alternative delivery address.
                        This will show a message to the user that there is a conflict and the checkout must be reloaded.
                    */
                    break;

                case 'collectorCheckoutExpired':
                    /*
                        Occurs when the checkout session indicated by the public token is no longer valid.
                        At the moment this is after 7 days since the cart was initialized.
                        An new cart initialization has to be made and the new public token set on a new loader script.
                    */
                    break;

                case 'collectorCheckoutResumed':
                    /*
                        Occurs when the checkout has loaded new data and is back in its normal state after a suspend.
                    */
                    break;
            }
        },

        lockCartForInput: function(lock) {
            if (lock) {
                $('.collectorcheckout-index-index .collector.action.qty-button').attr('disabled','disabled');
                $('.collectorcheckout-index-index .item-qty.cart-item-qty').attr('disabled','disabled');
                $('.collectorcheckout-index-index .co-shipping-method-form .radio').attr('disabled','disabled');
                $('.collectorcheckout-index-index .form-discount .input-text').attr('disabled','disabled');
                $('.collectorcheckout-index-index .form-discount .action').attr('disabled','disabled');
                $('.collectorcheckout-index-index .cart-items-remove-item').hide();
            } else{
                $('.collectorcheckout-index-index .collector.action.qty-button').removeAttr('disabled');
                $('.collectorcheckout-index-index .item-qty.cart-item-qty').removeAttr('disabled');
                $('.collectorcheckout-index-index .co-shipping-method-form .radio').removeAttr('disabled');
                $('.collectorcheckout-index-index .form-discount .input-text').removeAttr('disabled');
                $('.collectorcheckout-index-index .form-discount .action').removeAttr('disabled');
                $('.collectorcheckout-index-index .cart-items-remove-item').show();
            }
        },

        getRemoveItemImage: function() {

            return window.checkoutConfig.payment.collector_checkout.image_remove_item;
        },

        getPlusQtyImage: function() {

            return window.checkoutConfig.payment.collector_checkout.image_plus_qty;
        },

        getMinusQtyImage: function() {

            return window.checkoutConfig.payment.collector_checkout.image_minus_qty;
        },

        getReinitUrl: function() {

            return window.checkoutConfig.payment.collector_checkout.reinit_url;
        },

        getUpdateUrl: function(eventName, publicId) {
            return window.checkoutConfig.payment.collector_checkout.update_url + '?event=' + eventName + '&quoteid=' + publicId
        },

        fetchShippingRates: function() {
            var address = quote.shippingAddress();
            var type = address.getType();
            var rateProcessors = [];

            rateRegistry.set(address.getCacheKey(), null);
            rateRegistry.set(address.getKey(), null);

            rateProcessors['default'] = defaultProcessor;
            rateProcessors[type] ?
                rateProcessors[type].getRates(quote.shippingAddress()) :
                rateProcessors['default'].getRates(quote.shippingAddress());
        },
        setCheckoutData: function () {
            var self = this;
            if (!payload) {
                var payload = {};
            }
            return storage.post(
                self.getUpdateUrl("update", window.checkoutConfig.quoteData.collectorbank_public_id), JSON.stringify(payload), true
            ).fail(
                function (response) {
                    console.error(response);
                }
            ).success(
                function (response) {
                    var address = quote.shippingAddress();

                    if (address) {
                        address.postcode = response.postcode;
                        address.region = response.region;
                        address.countryId = response.country_id;
                    }

                    checkoutData.setSelectedShippingRate(response.shipping_method);

                    cartCache.clear('address');
                    cartCache.clear('totals');

                    self.fetchShippingRates();
                }
            );
        },
        addressUpdated: function(event) {
            var self = this;
            var payload = {}
            collectorIframe.suspend();

            return storage.post(
                self.getUpdateUrl(event.type, event.detail), JSON.stringify(payload), true
            ).fail(
                function (response) {
                    console.error(response);
                }
            ).success(
                function (response) {
                    var address = quote.shippingAddress();

                    if (address) {
                        address.postcode = response.postcode;
                        address.region = response.region;
                        address.countryId = response.country_id;
                    }

                    checkoutData.setSelectedShippingRate(response.shipping_method);

                    cartCache.clear('address');
                    cartCache.clear('totals');

                    self.fetchShippingRates();
                    collectorIframe.resume();
                }
            );
        },
        getCartItems: function() {

            return this.cartData().items;
        },

        plusQty: function(itemId) {
            return function () {
                var inputElem = $('#collector-cart-item-' + itemId + '-qty');
                var val = Number(inputElem.val());

                inputElem.val(++val).change();
            }
        },

        minusQty: function(itemId) {
            return function () {
                var inputElem = $('#collector-cart-item-' + itemId + '-qty');
                var val = Number(inputElem.val());

                inputElem.val(--val).change();
            }
        },

        debounce: function(func, wait, immediate) {
            var self = this;
            return function() {
                var context = this, args = arguments;
                var later = function() {
                    self.timeout = null;
                    if (!immediate) func.apply(context, args);
                };
                var callNow = immediate && !timeout;
                clearTimeout(self.timeout);
                self.timeout = setTimeout(later, wait);
                if (callNow) func.apply(context, args);
            };
        },

        updateItemQty: function(itemId) {
            var self = this;
            return function() {
                self._ajax(window.checkout.updateItemQtyUrl, {
                    'item_id': itemId,
                    'item_qty': $('#collector-cart-item-' + itemId + '-qty').val()
                }, itemId, self._updateItemQtyAfter);

            };
        },

        _updateItemQtyAfter: function (itemId) {
            var productData = this._getProductById(Number(itemId));

            if (!_.isUndefined(productData)) {
                $(document).trigger('ajax:updateCartItemQty');

                if (window.location.href === this.shoppingCartUrl) {
                    window.location.reload(false);
                }
            }
        },

        removeItem: function(itemId) {
            var self = this;
            return function() {
                confirm({
                    content: $.mage.__('Are you sure you would like to remove this item from the shopping cart?'),
                    actions: {
                        /** @inheritdoc */
                        confirm: function () {
                            self._ajax(window.checkout.removeItemUrl, {
                                'item_id': itemId
                            }, itemId, self._removeItemAfter);
                        },

                        /** @inheritdoc */
                        always: function (e) {
                            e.stopImmediatePropagation();
                        }
                    }
                });
            }
        },

        _removeItemAfter: function (itemId) {
            var productData = this._getProductById(Number(itemId));

            if (!_.isUndefined(productData)) {
                $(document).trigger('ajax:removeFromCart', {
                    productIds: [productData['product_id']]
                });
            }
        },

        _getProductById: function (productId) {
            return _.find(customerData.get('cart')().items, function (item) {
                return productId === Number(item['item_id']);
            });
        },

        _ajax: function (url, data, itemId, callback) {
            $.extend(data, {
               'form_key': $.mage.cookies.get('form_key')
            });

            $.ajax({
                url: url,
                data: data,
                type: 'post',
                dataType: 'json',
                context: this,

                /** @inheritdoc */
                beforeSend: function () {
                   collectorIframe.suspend();
                },

                /** @inheritdoc */
                complete: function () {
                }
            })
            .done(function (response) {
                var msg;

                if (response.success) {
                    callback.call(this, itemId, response);
                } else {
                    msg = response['error_message'];

                    if (msg) {
                        alert({
                            content: msg
                        });
                        collectorIframe.resume();
                    }
                }
            })
            .fail(function (error) {
                console.log(JSON.stringify(error));
            });
        },
    });
});
