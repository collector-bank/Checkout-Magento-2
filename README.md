# Collector Checkout for Magento 2

Configuration manual here: [Collector Bank Magento 2 configuration manual](docs/manual.md)

## Requirements
* Magento Open Source 2.2.0 or above
* PHP 7.2 and above

## Install
Run these commands from the Magento base folder:
* composer require collector-bank/collector-checkout-magento2
* bin/magento module:enable Webbhuset_CollectorCheckout
* bin/magento setup:upgrade && bin/magento setup:di:compile && bin/magento cache:flush

## Configure
After that, head to administration -> Stores -> Configuration -> Payment methods -> Collector Bank Checkout and click configure to configure Collector Checkout.

## Upgrade
To upgrade run composer update webbhuset/collector-checkout-magento2 --with-dependencies. Then run bin/magento setup:upgrade && bin/magento setup:di:compile && bin/magento cache:flush from your base folder.
