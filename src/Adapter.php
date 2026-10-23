<?php

namespace Webbhuset\CollectorCheckout;

use Webbhuset\CollectorCheckout\Exception\CanNotInitiateIframeException;
use Webbhuset\CollectorCheckout\Exception\ResponseErrorOnCartUpdate;
use Webbhuset\CollectorCheckout\Service\Sdk\Checkout\Adapter\CurlWithAccessKeyFactory;
use Webbhuset\CollectorCheckout\Service\Sdk\Checkout\Session;
use Webbhuset\CollectorCheckout\Service\Sdk\Checkout\SessionFactory;

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
     * @var Config\ConfigFactory
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
     * @var SessionFactory
     */
    private SessionFactory $sessionFactory;

    /**
     * @var CurlWithAccessKeyFactory
     */
    private CurlWithAccessKeyFactory $curlWithAccessKeyFactory;

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
        \Webbhuset\CollectorCheckout\Config\ConfigFactory $configFactory,
        \Webbhuset\CollectorCheckout\Logger\Logger $logger,
        SessionFactory $sessionFactory,
        CurlWithAccessKeyFactory $curlWithAccessKeyFactory
    ) {
        $this->quoteConverter       = $quoteConverter;
        $this->configFactory        = $configFactory;
        $this->quoteDataHandler     = $quoteDataHandler;
        $this->quoteUpdater         = $quoteUpdater;
        $this->quoteRepository      = $quoteRepository;
        $this->logger               = $logger;
        $this->orderDataHandler     = $orderDataHandler;
        $this->sessionFactory       = $sessionFactory;
        $this->curlWithAccessKeyFactory = $curlWithAccessKeyFactory;
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
            } catch (\Webbhuset\CollectorCheckout\Service\Sdk\Checkout\Errors\ResponseError $responseError) {
                if (900 == $responseError->getCode()
                    || 404 == $responseError->getCode()) {
                    $collectorSession = $this->initialize($quote);
                    $publicToken = $collectorSession->getPublicToken();
                } else {
                    $errorMsg = $responseError->getErrorLogMessageFromResponse();
                    $responseBody = $responseError->getResponseBody();
                    $response = $responseError->getResponse();
                    $request = $responseError->getRequest();

                    $logContext = [
                        'error_message' => $errorMsg,
                        'http_status' => $response['status'] ?? 'unknown',
                        'response_body' => $responseBody,
                        'response_header' => $response['header'] ?? '',
                        'request_data' => is_array($request) ? $request : json_decode($request, true),
                        'exception_code' => $responseError->getCode(),
                        'exception_message' => $responseError->getMessage(),
                    ];

                    $this->logger->addCritical("Response error when updating fees. " . $errorMsg, $logContext);

                    throw new ResponseErrorOnCartUpdate(
                        __('Please refresh the page and try again.')
                    );
                }
            } catch (\Webbhuset\CollectorCheckout\Exception\ResponseErrorOnCartUpdate $responseError) {
                $collectorSession = $this->initialize($quote);
                $publicToken = $collectorSession->getPublicToken();
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
    public function synchronize(\Magento\Quote\Model\Quote $quote, $eventName = null)
    {
        $shippingAddress = $quote->getShippingAddress();
        $checkoutData = $this->acquireCheckoutInformationFromQuote($quote);
        $quote = $this->quoteUpdater->setQuoteData($quote, $checkoutData);

        $rate = $shippingAddress->getShippingRateByCode($shippingAddress->getShippingMethod());
        if (!$rate || !$shippingAddress->getShippingMethod()) {
            $this->quoteUpdater->setDefaultShippingMethod($quote);
        }
        $config = $this->configFactory->create(['storeId' => (int)$quote->getStoreId()]);
        if ('collectorCheckoutShippingUpdated' === $eventName
            && $config->getIsDeliveryCheckoutActive()
        ) {
            $this->quoteRepository->save($quote);
            $quote->getShippingAddress()->setCollectShippingRates(true)->collectShippingRates();
            return $quote;
        }
        if($checkoutData->getStatus()->getStatus() !== \Webbhuset\CollectorCheckout\Service\Sdk\Checkout\Checkout\Status::COMMITTED_TO_PURCHASE) {
            $this->updateFees($quote);
            $this->updateCart($quote);
            $this->quoteRepository->save($quote);
        }
        return $quote;
    }

    /**
     * @param int $storeId
     * @return bool
     */
    private function isFallbackDeliveryMethodConfigured(int $storeId): bool
    {
        $config = $this->configFactory->create(['storeId' => $storeId]);
        return $config->getIsDeliveryCheckoutActive()
            && $config->getDeliveryCheckoutFallbackDescription()
            && $config->getDeliveryCheckoutFallbackTitle();
    }

    /**
     * Initializes a new iframe
     *
     * @param \Magento\Quote\Model\Quote $quote
     * @return \Webbhuset\CollectorCheckout\Service\Sdk\Checkout\Session
     * @throws \Magento\Framework\Exception\LocalizedException
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function initialize(\Magento\Quote\Model\Quote $quote) : \Webbhuset\CollectorCheckout\Service\Sdk\Checkout\Session
    {
        $config = $this->configFactory->create(['storeId' => (int)$quote->getStoreId()]);
        $customerType = (int)$this->quoteDataHandler->getCustomerType($quote) ?: $config->getDefaultCustomerType();
        $config->setCustomerType($customerType);
        $quote = $this->quoteUpdater->setDefaultShippingIfEmpty($quote);

        $cart = $this->quoteConverter->getCart($quote);

        if (!$this->isFallbackDeliveryMethodConfigured((int)$quote->getStoreId())
            || $config->getIsCustomDeliveryAdapter()) {
            $fees = $this->quoteConverter->getFees($quote);
        } else {
            $fees = $this->quoteConverter->getFallbackFees($quote);
        }

        $initCustomer = $this->quoteConverter->getInitializeCustomer($quote);

        $countryCode = $config->getCountryCode();
        $adapter = $this->getAdapter($config);

        $collectorSession = $this->sessionFactory->create(['adapter' => $adapter]);

        try {
            $collectorSession->initialize(
                $config,
                $fees,
                $cart,
                $countryCode,
                $initCustomer
            );

            $storeId = $config->getStoreId();

            $this->quoteDataHandler->setPrivateId($quote, $collectorSession->getPrivateId())
                ->setPublicToken($quote, $collectorSession->getPublicToken())
                ->setCustomerType($quote, $customerType)
                ->setStoreId($quote, $storeId);

            $quote->collectTotals();

            $this->quoteRepository->save($quote);
        } catch (\Webbhuset\CollectorCheckout\Service\Sdk\Checkout\Errors\ResponseError $e) {
            $errorMsg = $e->getErrorLogMessageFromResponse();
            $responseBody = $e->getResponseBody();
            $response = $e->getResponse();

            $logContext = [
                'error_message' => $errorMsg,
                'http_status' => $response['status'] ?? 'unknown',
                'response_body' => $responseBody,
                'response_header' => $response['header'] ?? '',
                'exception_code' => $e->getCode(),
                'exception_message' => $e->getMessage(),
            ];

            $this->logger->addCritical(
                "Response error when initiating iframe: " . $errorMsg,
                $logContext
            );

            throw new CanNotInitiateIframeException(
                __('Can not initiate payment window. Check var/log/collectorbank.log for error details.')
            );
        }

        return $collectorSession;
    }

    public function initWithCustomerType(\Magento\Quote\Model\Quote $quote, int $customerType)
    {
        $config = $this->configFactory->create(['storeId' => (int)$quote->getStoreId()]);

        $this->quoteDataHandler->setCustomerType($quote, $customerType);
        if (\Webbhuset\CollectorCheckout\Config\Source\Customer\DefaultType::PRIVATE_CUSTOMERS == $customerType) {
            $storeId = $config->getB2CStoreId();
        } else {
            $storeId =  $config->getB2BStoreId();
        }

        $this->quoteDataHandler->setStoreId($quote, $storeId);

        $collectorSession = $this->initialize($quote);
        $publicToken = $collectorSession->getPublicToken();

        return $publicToken;
    }

    /**
     * Acquires information from collector bank about the current session
     *
     * @param \Magento\Quote\Model\Quote $quote
     * @return \Webbhuset\CollectorCheckout\Service\Sdk\Checkout\CheckoutData
     */
    public function acquireCheckoutInformationFromQuote(\Magento\Quote\Model\Quote $quote): \Webbhuset\CollectorCheckout\Service\Sdk\Checkout\CheckoutData
    {
        $config = $this->configFactory->create(['storeId' => (int)$quote->getStoreId()]);
        $privateId = $this->quoteDataHandler->getPrivateId($quote);
        $data = $this->acquireCheckoutInformation($config, $privateId);

        return $data;
    }

    /**
     * Acquires information from collector bank from an order
     *
     * @param \Magento\Quote\Model\Quote $quote
     * @return \Webbhuset\CollectorCheckout\Service\Sdk\Checkout\CheckoutData
     */
    public function acquireCheckoutInformationFromOrder(\Magento\Sales\Api\Data\OrderInterface $order): \Webbhuset\CollectorCheckout\Service\Sdk\Checkout\CheckoutData
    {
        $config = $this->configFactory->create(['storeId' => (int)$order->getStoreId()]);
        $privateId = $this->orderDataHandler->getPrivateId($order);

        $data = $this->acquireCheckoutInformation($config, $privateId);

        return $data;
    }

    /**
     * Acquires information from collector bank about the current session from privateId
     *
     * @param \Webbhuset\CollectorCheckout\Config\Config $config
     * @param int $privateId
     * @return \Webbhuset\CollectorCheckout\Service\Sdk\Checkout\CheckoutData
     */
    public function acquireCheckoutInformation($config, $privateId): \Webbhuset\CollectorCheckout\Service\Sdk\Checkout\CheckoutData
    {
        $adapter = $this->getAdapter($config);

        $collectorSession = $this->sessionFactory->create(['adapter' => $adapter]);
        $collectorSession->load($privateId);

        return $collectorSession->getCheckoutData();
    }

    /**
     * Update fees in the collector bank session
     *
     * @param \Magento\Quote\Model\Quote $quote
     * @return \Webbhuset\CollectorCheckout\Service\Sdk\Checkout\Session
     * @throws \Magento\Framework\Exception\LocalizedException
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function updateFees(\Magento\Quote\Model\Quote $quote) : \Webbhuset\CollectorCheckout\Service\Sdk\Checkout\Session
    {
        $config = $this->configFactory->create(['storeId' => (int)$quote->getStoreId()]);
        $adapter = $this->getAdapter($config);
        $collectorSession = $this->sessionFactory->create(['adapter' => $adapter]);

        if ($config->getIsDeliveryCheckoutActive()) {
            return $collectorSession;
        }

        $fees = $this->quoteConverter->getFees($quote);
        $privateId = $this->quoteDataHandler->getPrivateId($quote);

        $maxRetries = 3;
        for ($attempt = 1; $attempt <= $maxRetries; $attempt++) {
            try {
                if (!empty($fees->toArray())) {
                    $collectorSession->setPrivateId($privateId)
                        ->updateFees($fees);
                }
                return $collectorSession;
            } catch (\Webbhuset\CollectorCheckout\Service\Sdk\Checkout\Errors\ResponseError $e) {
                if ((int)$e->getCode() === 423 && $attempt < $maxRetries) {
                    usleep(300000 * $attempt);
                    continue;
                }

                if ((int)$e->getCode() === 900) {
                    $this->logger->addInfo(
                        'Skipping fee update because purchase is already completed.',
                        [
                            'private_id' => $privateId,
                            'exception_code' => $e->getCode(),
                            'exception_message' => $e->getMessage(),
                        ]
                    );
                    return $collectorSession;
                }

                $errorMsg = $e->getErrorLogMessageFromResponse();
                $responseBody = $e->getResponseBody();
                $response = $e->getResponse();
                $request = $e->getRequest();

                $logContext = [
                    'error_message' => $errorMsg,
                    'http_status' => $response['status'] ?? 'unknown',
                    'response_body' => $responseBody,
                    'response_header' => $response['header'] ?? '',
                    'request_data' => is_array($request) ? $request : json_decode($request, true),
                    'exception_code' => $e->getCode(),
                    'exception_message' => $e->getMessage(),
                ];

                $this->logger->addCritical("Response error when updating fees. " . $errorMsg, $logContext);

                throw new ResponseErrorOnCartUpdate(
                    __('Please refresh the page and try again.')
                );
            }
        }

        return $collectorSession;
    }

    /**
     *
     *
     * @param \Magento\Quote\Model\Quote $quote
     * @return \Webbhuset\CollectorCheckout\Service\Sdk\Checkout\Session
     * @throws \Magento\Framework\Exception\LocalizedException
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function updateCart(\Magento\Quote\Model\Quote $quote) : \Webbhuset\CollectorCheckout\Service\Sdk\Checkout\Session
    {
        $config = $this->configFactory->create(['storeId' => (int)$quote->getStoreId()]);
        $adapter = $this->getAdapter($config);
        $collectorSession = $this->sessionFactory->create(['adapter' => $adapter]);
        $cart = $this->quoteConverter->getCart($quote);
        $privateId = $this->quoteDataHandler->getPrivateId($quote);

        $maxRetries = 3;
        for ($attempt = 1; $attempt <= $maxRetries; $attempt++) {
            try {
                if (!empty($cart->getItems())) {
                    $collectorSession->setPrivateId($privateId)
                        ->updateCart($cart);
                }
                return $collectorSession;
            } catch (\Webbhuset\CollectorCheckout\Service\Sdk\Checkout\Errors\ResponseError $e) {
                if ((int)$e->getCode() === 423 && $attempt < $maxRetries) {
                    usleep(300000 * $attempt);
                    continue;
                }

                if ((int)$e->getCode() === 900) {
                    $this->logger->addInfo(
                        'Skipping cart update because purchase is already completed.',
                        [
                            'private_id' => $privateId,
                            'exception_code' => $e->getCode(),
                            'exception_message' => $e->getMessage(),
                        ]
                    );
                    return $collectorSession;
                }

                $errorMsg = $e->getErrorLogMessageFromResponse();
                $responseBody = $e->getResponseBody();
                $response = $e->getResponse();
                $request = $e->getRequest();

                $logContext = [
                    'error_message' => $errorMsg,
                    'http_status' => $response['status'] ?? 'unknown',
                    'response_body' => $responseBody,
                    'response_header' => $response['header'] ?? '',
                    'request_data' => is_array($request) ? $request : json_decode($request, true),
                    'exception_code' => $e->getCode(),
                    'exception_message' => $e->getMessage(),
                ];

                $this->logger->addCritical("Response error when updating cart. " . $errorMsg, $logContext);

                throw new ResponseErrorOnCartUpdate(
                    __('Please refresh the page and try again.')
                );
            }
        }

        return $collectorSession;
    }

    /**
     * Get adapter
     *
     * @param \Webbhuset\CollectorCheckout\Config\Config $config
     * @return \Webbhuset\CollectorCheckout\Service\Sdk\Checkout\Adapter\AdapterInterface
     */
    public function getAdapter($config) : \Webbhuset\CollectorCheckout\Service\Sdk\Checkout\Adapter\AdapterInterface
    {

        return $this->curlWithAccessKeyFactory->create(['config' => $config]);
    }
}
