<<<<<<< HEAD
# Walley Checkout for Magento 2 (previous Collector Checkout)
=======
# Walley Checkout for Magento 2
>>>>>>> Name change to Walley

Configuration manual here: [Walley Magento 2 configuration manual](docs/manual.md)

Technical information how to fetch delivery checkout data from the order. [Delivery checkout technical integration](docs/deliveryCheckoutIntegration.md)

## Requirements
* Magento Open Source 2.2.0 or above
* PHP 7.4 and above

## Install
Run these commands from the Magento base folder:
* composer require collector-bank/collector-checkout-magento2
* bin/magento module:enable Webbhuset_CollectorCheckout
* bin/magento setup:upgrade && bin/magento setup:di:compile && bin/magento cache:flush

## Configure
After that, head to administration -> Stores -> Configuration -> Payment methods -> Walley Checkout and click configure to configure Walley Checkout.

## Upgrade
To upgrade run 
* composer update collector-bank/collector-checkout-magento2 --with-dependencies

Then run 
* bin/magento setup:upgrade && bin/magento setup:di:compile && bin/magento cache:flush 
from your base folder.

