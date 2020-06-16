<?php

namespace Webbhuset\CollectorCheckout;

use Magento\Framework\Phrase;
use Webbhuset\CollectorCheckout\Exception\CanNotInitiateIframeException;
use Webbhuset\CollectorCheckout\Exception\ResponseErrorOnCartUpdate;

/**
 * Class Adapter
 *
 * @package Webbhuset\CollectorCheckout
 */
class Adapter
{
    /**
     * @var QuoteConverter
     */
    protected $quoteConverter;
    /**
     * @var Config\OrderConfigFactory
     */
    protected $orderConfigFactory;
    /**
     * @var Data\QuoteHandler
     */
    protected $configFactory;
    /**
     * @var Data\QuoteHandler
     */
    protected $quoteDataHandler;
    /**
     * @var Data\OrderHandler
     */
    protected $orderDataHandler;
    /**
     * @var QuoteUpdater
     */
    protected $quoteUpdater;
    /**
     * @var \Magento\Quote\Api\CartRepositoryInterface
     */
    protected $quoteRepository;
    /**
     * @var Logger\Logger
     */
    protected $logger;

    /**
     * Adapter constructor.
     *
     * @param QuoteConverter                             $quoteConverter
     * @param QuoteUpdater                               $quoteUpdater
     * @param Data\QuoteHandler                          $quoteDataHandler
     * @param \Magento\Quote\Api\CartRepositoryInterface $quoteRepository
     * @param Data\OrderHandler                          $orderDataHandler
     * @param Config\Config                              $config
     * @param Logger\Logger                              $logger
     */
    public function __construct(
        \Webbhuset\CollectorCheckout\QuoteConverter $quoteConverter,
        \Webbhuset\CollectorCheckout\QuoteUpdater $quoteUpdater,
        \Webbhuset\CollectorCheckout\Data\QuoteHandler $quoteDataHandler,
        \Webbhuset\CollectorCheckout\Data\OrderHandler $orderDataHandler,
        \Magento\Quote\Api\CartRepositoryInterface $quoteRepository,
        \Webbhuset\CollectorCheckout\Config\QuoteConfigFactory $configFactory,
        \Webbhuset\CollectorCheckout\Config\OrderConfigFactory $orderConfigFactory,
        \Webbhuset\CollectorCheckout\Logger\Logger $logger
    ) {
        $this->quoteConverter       = $quoteConverter;
        $this->orderConfigFactory   = $orderConfigFactory;
        $this->configFactory        = $configFactory;
        $this->quoteDataHandler     = $quoteDataHandler;
        $this->quoteUpdater         = $quoteUpdater;
        $this->quoteRepository      = $quoteRepository;
        $this->logger               = $logger;
        $this->orderDataHandler     = $orderDataHandler;
    }

    /**
     * Init or syncs the iframe and updates the necessary data on quote (e.g. public and private token)
     *
     * @param \Magento\Quote\Model\Quote $quote
     * @return string
     * @throws \Exception
     */
    public function initOrSync(\Magento\Quote\Model\Quote $quote) : string
    {
        $publicToken = $this->quoteDataHandler->getPublicToken($quote);
        if ($publicToken) {
            try {
                $this->synchronize($quote);
            } catch (\Webbhuset\CollectorCheckoutSDK\Errors\ResponseError $responseError) {
                if (900 == $responseError->getCode()
                    || 404 == $responseError->getCode() ){

                    $collectorSession = $this->initialize($quote);
                    $publicToken = $collectorSession->getPublicToken();
                } else {
                    $errorMsg = $e->getErrorLogMessageFromResponse();
                    $this->logger->addCritical("Response error when updating fees. " . $errorMsg);

                    throw new ResponseErrorOnCartUpdate(
                        new Phrase(
                            'Response error when updating fees. Check var/log/collectorbank.log for error details.'
                        )
                    );
                }
            }
        } else {
            $collectorSession = $this->initialize($quote);
            $publicToken = $collectorSession->getPublicToken();
        }

        return $publicToken;
    }

    /**
     * Fetch addresses from collector order,
     * set address on magento quote,
     * update fees and cart if needed
     *
     * @param \Magento\Quote\Api\Data\CartInterface $quote
     * @return \Magento\Quote\Api\Data\CartInterface
     * @throws \Exception
     */
    public function synchronize(\Magento\Quote\Model\Quote $quote)
    {
        $shippingAddress = $quote->getShippingAddress();

        $shippingAddress->setCollectShippingRates(true)
            ->collectShippingRates();

        $checkoutData = $this->acquireCheckoutInformationFromQuote($quote);
        $oldFees = $checkoutData->getFees();
        $oldCart = $checkoutData->getCart();
        $quote = $this->quoteUpdater->setQuoteData($quote, $checkoutData);


        $rate = $shippingAddress->getShippingRateByCode($shippingAddress->getShippingMethod());
        if (!$rate || !$shippingAddress->getShippingMethod()) {
            $this->quoteUpdater->setDefaultShippingMethod($quote);
        }

        $quote->collectTotals();

        $quote->setNeedsCollectorUpdate(null);
        $this->quoteRepository->save($quote);

        $this->updateFees($quote);
        $this->updateCart($quote);

        return $quote;
    }

    /**
     * Initializes a new iframe
     *
     * @param \Magento\Quote\Model\Quote $quote
     * @return \Webbhuset\CollectorCheckoutSDK\Session
     * @throws \Magento\Framework\Exception\LocalizedException
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function initialize(\Magento\Quote\Model\Quote $quote) : \Webbhuset\CollectorCheckoutSDK\Session
    {
        $config = $this->configFactory->create(['quote' => $quote]);
        $quote = $this->quoteUpdater->setDefaultShippingIfEmpty($quote);

        $cart = $this->quoteConverter->getCart($quote);
        $fees = $this->quoteConverter->getFees($quote);
        $initCustomer = $this->quoteConverter->getInitializeCustomer($quote);

        $countryCode = $config->getCountryCode();
        $adapter = $this->getAdapter($config);

        $collectorSession = new \Webbhuset\CollectorCheckoutSDK\Session($adapter);

        try {
            $collectorSession->initialize(
                $config,
                $fees,
                $cart,
                $countryCode,
                $initCustomer
            );

            $customerType = $this->quoteDataHandler->getCustomerType($quote) ?? $config->getDefaultCustomerType();
            $storeId = $config->getStoreId();

            $this->quoteDataHandler->setPrivateId($quote, $collectorSession->getPrivateId())
                ->setPublicToken($quote, $collectorSession->getPublicToken())
                ->setCustomerType($quote, $customerType)
                ->setStoreId($quote, $storeId);

            $quote->collectTotals();

            $this->quoteRepository->save($quote);
        } catch (\Webbhuset\CollectorCheckoutSDK\Errors\ResponseError $e) {
            $errorMsg = $e->getErrorLogMessageFromResponse();
            $this->logger->addCritical("Response error when initiating iframe " . $errorMsg);

            throw new CanNotInitiateIframeException(
                new Phrase(
                    'Can not initiate payment window. Check var/log/collectorbank.log for error details.'
                )
            );
        }

        return $collectorSession;
    }


    /**
     * Acquires information from collector bank about the current session
     *
     * @param \Magento\Quote\Model\Quote $quote
     * @return \Webbhuset\CollectorCheckoutSDK\CheckoutData
     */
    public function acquireCheckoutInformationFromQuote(\Magento\Quote\Model\Quote $quote): \Webbhuset\CollectorCheckoutSDK\CheckoutData
    {
        $config = $this->configFactory->create(['quote' => $quote]);
        $privateId = $this->quoteDataHandler->getPrivateId($quote);
        $data = $this->acquireCheckoutInformation($config, $privateId);

        return $data;
    }

    /**
     * Acquires information from collector bank from an order
     *
     * @param \Magento\Quote\Model\Quote $quote
     * @return \Webbhuset\CollectorCheckoutSDK\CheckoutData
     */
    public function acquireCheckoutInformationFromOrder(\Magento\Sales\Api\Data\OrderInterface $order): \Webbhuset\CollectorCheckoutSDK\CheckoutData
    {
        $config = $this->orderConfigFactory->create(['order' => $order]);
        $privateId = $this->orderDataHandler->getPrivateId($order);

        $data = $this->acquireCheckoutInformation($config, $privateId);

        return $data;
    }

    /**
     * Acquires information from collector bank about the current session from privateId
     *
     * @param \Webbhuset\CollectorCheckout\Config\QuoteConfig $privateId
     * @param int $privateId
     * @return \Webbhuset\CollectorCheckoutSDK\CheckoutData
     */
    public function acquireCheckoutInformation($config, $privateId): \Webbhuset\CollectorCheckoutSDK\CheckoutData
    {
        $adapter = $this->getAdapter($config);

        $collectorSession = new \Webbhuset\CollectorCheckoutSDK\Session($adapter);
        $collectorSession->load($privateId);

        return $collectorSession->getCheckoutData();
    }

    /**
     * Update fees in the collector bank session
     *
     * @param \Magento\Quote\Model\Quote $quote
     * @return \Webbhuset\CollectorCheckoutSDK\Session
     * @throws \Magento\Framework\Exception\LocalizedException
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function updateFees(\Magento\Quote\Model\Quote $quote) : \Webbhuset\CollectorCheckoutSDK\Session
    {
        /** @var \Webbhuset\CollectorCheckout\Config\QuoteConfig $config */
        $config = $this->configFactory->create(['quote' => $quote]);
        $adapter = $this->getAdapter($config);
        $collectorSession = new \Webbhuset\CollectorCheckoutSDK\Session($adapter);

        if($config->getIsDeliveryCheckoutActive()) {

            return $collectorSession;
        }

        $fees = $this->quoteConverter->getFees($quote);
        $privateId = $this->quoteDataHandler->getPrivateId($quote);

        try {
            if (!empty($fees->toArray())) {
                $collectorSession->setPrivateId($privateId)
                    ->updateFees($fees);
            }
        } catch (\Webbhuset\CollectorCheckoutSDK\Errors\ResponseError $e) {
            $errorMsg = $e->getErrorLogMessageFromResponse();
            $this->logger->addCritical("Response error when updating fees. " . $errorMsg);

            throw new ResponseErrorOnCartUpdate(
                new Phrase(
                    'Response error when updating fees. Check var/log/collectorbank.log for error details.'
                )
            );
        }

        return $collectorSession;
    }

    /**
     *
     *
     * @param \Magento\Quote\Model\Quote $quote
     * @return \Webbhuset\CollectorCheckoutSDK\Session
     * @throws \Magento\Framework\Exception\LocalizedException
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function updateCart(\Magento\Quote\Model\Quote $quote) : \Webbhuset\CollectorCheckoutSDK\Session
    {
        $config = $this->configFactory->create(['quote' => $quote]);
        $adapter = $this->getAdapter($config);
        $collectorSession = new \Webbhuset\CollectorCheckoutSDK\Session($adapter);
        $cart = $this->quoteConverter->getCart($quote);
        $privateId = $this->quoteDataHandler->getPrivateId($quote);

        try {
            if (!empty($cart->getItems())) {
                $collectorSession->setPrivateId($privateId)
                    ->updateCart($cart);
            }
        } catch (\Webbhuset\CollectorCheckoutSDK\Errors\ResponseError $e) {
            $errorMsg = $e->getErrorLogMessageFromResponse();
            $this->logger->addCritical("Response error when updating cart. " . $errorMsg);

            throw new ResponseErrorOnCartUpdate(
                new Phrase(
                    'Response error when updating cart. Examine error log at var/log/collectorbank.log for more details.'
                )
            );
        }

        return $collectorSession;
    }

    /**
     * Get adapter
     *
     * @param \Webbhuset\CollectorCheckout\Config\QuoteConfig $config
     * @return \Webbhuset\CollectorCheckoutSDK\Adapter\AdapterInterface
     */
    public function getAdapter($config) : \Webbhuset\CollectorCheckoutSDK\Adapter\AdapterInterface
    {
        if ($config->getIsMockMode()) {
            return new \Webbhuset\CollectorCheckoutSDK\Adapter\MockAdapter($config);
        }

        return new \Webbhuset\CollectorCheckoutSDK\Adapter\CurlAdapter($config);
    }
}
