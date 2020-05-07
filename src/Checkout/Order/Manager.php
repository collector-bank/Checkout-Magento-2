<?php

namespace Webbhuset\CollectorCheckout\Checkout\Order;

use Webbhuset\CollectorCheckoutSDK\Checkout\Purchase\Result as PurchaseResult;

/**
 * Class Manager
 *
 * @package Webbhuset\CollectorCheckout\Checkout\Order
 */
class Manager
{
    /**
     * @var \Magento\Quote\Api\CartManagementInterface
     */
    protected $cartManagement;
    /**
     * @var \Magento\Sales\Model\OrderRepository
     */
    protected $orderRepository;
    /**
     * @var \Webbhuset\CollectorCheckout\AdapterFactory
     */
    protected $collectorAdapter;
    /**
     * @var \Webbhuset\CollectorCheckout\Data\OrderHandler
     */
    protected $orderHandler;
    /**
     * @var \Magento\Framework\Api\SearchCriteriaBuilder
     */
    protected $searchCriteriaBuilder;
    /**
     * @var \Webbhuset\CollectorCheckout\Config\OrderConfigFactory
     */
    protected $configFactory;
    /**
     * @var \Magento\Sales\Api\OrderManagementInterface
     */
    protected $orderManagement;
    /**
     * @var \Magento\Quote\Model\QuoteManagement
     */
    protected $quoteManagement;
    /**
     * @var ManagerFactory
     */
    protected $orderManager;
    /**
     * @var \Magento\Framework\Registry
     */
    protected $registry;
    /**
     * @var \Magento\Framework\Stdlib\DateTime\DateTimeFactory
     */
    protected $dateTime;
    /**
     * @var \Webbhuset\CollectorCheckout\Invoice\AdministrationFactory
     */
    protected $invoice;
    /**
     * @var \Webbhuset\CollectorCheckout\Logger\Logger
     */
    protected $logger;
    /**
     * @var \Magento\Newsletter\Model\SubscriberFactory
     */
    protected $subscriberFactory;

    /**
     * @var \Webbhuset\CollectorCheckout\Config\Config|\Webbhuset\CollectorCheckout\Config\ConfigFactory
     */
    protected $config;
    protected $carrierManager;

    /**
     * Manager constructor.
     *
     * @param \Magento\Quote\Api\CartManagementInterface                     $cartManagement
     * @param \Magento\Sales\Model\OrderRepository                           $orderRepository
     * @param \Webbhuset\CollectorCheckout\Data\OrderHandler             $orderHandler
     * @param \Magento\Framework\Api\SearchCriteriaBuilder                   $searchCriteriaBuilder
     * @param \Webbhuset\CollectorCheckout\AdapterFactory                $collectorAdapter
     * @param \Magento\Sales\Api\OrderManagementInterface                    $orderManagement
     * @param \Webbhuset\CollectorCheckout\Config\ConfigFactory          $config
     * @param \Magento\Quote\Model\QuoteManagement                           $quoteManagement
     * @param ManagerFactory                                                 $orderManager
     * @param \Magento\Framework\Registry                                    $registry
     * @param \Magento\Framework\Stdlib\DateTime\DateTimeFactory             $dateTime
     * @param \Webbhuset\CollectorCheckout\Invoice\AdministrationFactory $invoice
     * @param \Webbhuset\CollectorCheckout\Logger\Logger                 $logger
     * @param \Magento\Newsletter\Model\SubscriberFactory                    $subscriberFactory
     */
    public function __construct(
        \Magento\Quote\Api\CartManagementInterface $cartManagement,
        \Magento\Sales\Model\OrderRepository $orderRepository,
        \Webbhuset\CollectorCheckout\Data\OrderHandler $orderHandler,
        \Magento\Framework\Api\SearchCriteriaBuilder $searchCriteriaBuilder,
        \Webbhuset\CollectorCheckout\AdapterFactory $collectorAdapter,
        \Magento\Sales\Api\OrderManagementInterface $orderManagement,
        \Webbhuset\CollectorCheckout\Config\OrderConfigFactory $configFactory,
        \Magento\Quote\Model\QuoteManagement $quoteManagement,
        \Webbhuset\CollectorCheckout\Checkout\Order\ManagerFactory $orderManager,
        \Magento\Framework\Registry $registry,
        \Magento\Framework\Stdlib\DateTime\DateTimeFactory $dateTime,
        \Webbhuset\CollectorCheckout\Invoice\AdministrationFactory $invoice,
        \Webbhuset\CollectorCheckout\Logger\Logger $logger,
        \Magento\Newsletter\Model\SubscriberFactory $subscriberFactory,
        \Webbhuset\CollectorCheckout\Config\Config $config,
        \Webbhuset\CollectorCheckout\Carrier\Manager $carrierManager
    ) {
        $this->cartManagement        = $cartManagement;
        $this->collectorAdapter      = $collectorAdapter;
        $this->orderRepository       = $orderRepository;
        $this->searchCriteriaBuilder = $searchCriteriaBuilder;
        $this->configFactory         = $configFactory;
        $this->orderManagement       = $orderManagement;
        $this->orderHandler          = $orderHandler;
        $this->quoteManagement       = $quoteManagement;
        $this->orderManager          = $orderManager;
        $this->registry              = $registry;
        $this->dateTime              = $dateTime;
        $this->invoice               = $invoice;
        $this->logger                = $logger;
        $this->subscriberFactory     = $subscriberFactory;
        $this->config                = $config;
        $this->carrierManager        = $carrierManager;
    }

    /**
     * Create order from quote and return increment order id
     *
     * @param $quoteId
     * @return int incrementOrderId
     * @throws \Magento\Framework\Exception\CouldNotSaveException
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function createOrder(\Magento\Quote\Model\Quote $quote): string
    {
        $quoteId = $quote->getId();
        $orderId = $this->quoteManagement->placeOrder($quoteId);

        $order = $this->orderRepository->get($orderId);
        $incrementOrderId = $order->getIncrementId();

        $this->logger->addInfo(
            "Submitted order order id: {$incrementOrderId}. qouteId: {$quoteId} "
        );

        return $incrementOrderId;
    }

    /**
     * Delete order
     *
     * @param $order
     */
    public function deleteOrder($order)
    {
        $this->registry->register('isSecureArea', 'true');

        $this->orderRepository->delete($order);
        $this->logger->addInfo(
            "Delete order {$order->getIncrementId()}. qouteId: {$order->getQuoteId()} "
        );
        $this->registry->unregister('isSecureArea', 'true');
    }

    /**
     * Removes the order with reference / public token if the order is in STATE_NEW
     *
     * @param $reference
     */
    public function removeNewOrdersByPublicToken($reference)
    {
        try {
            $order = $this->orderManager->create()->getOrderByPublicToken($reference);
            if (\Magento\Sales\Model\Order::STATE_NEW == $order->getState()) {
                $this->removeOrderIfExists($order);
            }
        } catch (\Magento\Framework\Exception\NoSuchEntityException $e) {
        }
    }

    /**
     * Removes the order if it exists
     *
     * @param \Magento\Sales\Api\Data\OrderInterface $order
     */
    public function removeOrderIfExists(\Magento\Sales\Api\Data\OrderInterface $order)
    {
        try {
            $this->orderManagement->cancel($order->getId());

            $this->deleteOrder($order);
        } catch (\Magento\Framework\Exception\NoSuchEntityException $e) {
        }
    }

    /**
     * Handles notification callbacks and take different actions based on payment result
     *
     * @param \Magento\Sales\Api\Data\OrderInterface $order
     * @return array
     * @throws \Exception
     */
    public function notificationCallbackHandler(\Magento\Sales\Api\Data\OrderInterface $order): array
    {
        if (\Magento\Sales\Model\Order::STATE_CANCELED == $order->getState()
            || \Magento\Sales\Model\Order::STATE_COMPLETE == $order->getState()
        ) {
            $orderState = $order->getState();
            throw new \Exception("Order state is $orderState, order status can not be changed");
        }
        if ($order->getTotalInvoiced() > 0) {
            $totalAmount = $order->getTotalInvoiced();
            $this->logger->addCritical(
                "Can not invoice order, already invoiced: {$order->getIncrementId()}. qouteId: {$order->getQuoteId()} "
            );

            throw new \Exception("Order already invoiced in Magento for $totalAmount");
        }

        $collectorBankPrivateId = $this->orderHandler->getPrivateId($order);

        $checkoutAdapter = $this->collectorAdapter->create();
        $storeId = $this->orderHandler->getStoreId($order);

        $config = $this->configFactory->create(['order' => $order]);
        $checkoutData = $checkoutAdapter->acquireCheckoutInformation($config, $collectorBankPrivateId);

        $paymentResult = $checkoutData->getPurchase()->getResult()->getResult();

        $result = "";
        switch ($paymentResult) {
            case PurchaseResult::PRELIMINARY:
                $result = $this->acknowledgeOrder($order, $checkoutData);
                $this->orderRepository->save($order);

                if ($config->getIsDeliveryCheckoutActive()) {

                    $order = $this->carrierManager->saveShipmentDataOnOrder($order->getId(), $checkoutData);
                }
                break;

            case PurchaseResult::ON_HOLD:
                $result = $this->holdOrder($order, $checkoutData);
                $this->orderRepository->save($order);
                break;

            case PurchaseResult::REJECTED:
                $result = $this->cancelOrder($order, $checkoutData);
                $this->orderRepository->save($order);
                break;

            case PurchaseResult::ACTIVATED:
                $result = $this->activateOrder($order, $checkoutData);
                $this->orderRepository->save($order);
                break;
        }

        return $result;
    }

    /**
     * Acknowledged orders by adding payment information and changes state to processing
     *
     * @param \Magento\Sales\Api\Data\OrderInterface  $order
     * @param \Webbhuset\CollectorCheckoutSDK\CheckoutData $checkoutData
     * @return array
     * @throws \Exception
     */
    public function acknowledgeOrder(
        \Magento\Sales\Api\Data\OrderInterface $order,
        \Webbhuset\CollectorCheckoutSDK\CheckoutData $checkoutData
    ):array {
        $orderStatusBefore = $this->orderManagement->getStatus($order->getId());
        $config = $this->configFactory->create(['order' => $order]);
        $orderStatusAfter  = $config->getOrderStatusAcknowledged();

        if ($orderStatusAfter == $orderStatusBefore) {
            return [
                'message' => 'Order status already set to: ' . $orderStatusAfter
            ];
        }

        $this->unHoldOrder($order);

        $this->addPaymentInformation(
            $order->getPayment(),
            $checkoutData->getPurchase()
        );

        $this->updateOrderStatus(
            $order,
            $orderStatusAfter,
            \Magento\Sales\Model\Order::STATE_PROCESSING
        );

        $this->logger->addInfo(
            "Acknowledged order orderId: {$order->getIncrementId()}. qouteId: {$order->getQuoteId()} "
        );

        $this->orderManagement->notify($order->getEntityId());

        if ($this->orderHandler->getNewsletterSubscribe($order)) {
            $this->subscriberFactory->create()->subscribe($order->getCustomerEmail());
        }

        return [
            'order_status_before' => $orderStatusBefore,
            'order_status_after' => $orderStatusAfter
        ];
    }

    /**
     * Sets the order to On Hold
     *
     * @param \Magento\Sales\Api\Data\OrderInterface  $order
     * @param \Webbhuset\CollectorCheckoutSDK\CheckoutData $checkoutData
     * @return array
     */
    public function holdOrder(
        \Magento\Sales\Api\Data\OrderInterface $order,
        \Webbhuset\CollectorCheckoutSDK\CheckoutData $checkoutData
    ):array {
        $orderStatusBefore = $this->orderManagement->getStatus($order->getId());
        $orderStatusAfter  = $this->configFactory->create(['order' => $order])->getOrderStatusHolded();

        if ($orderStatusBefore == $orderStatusAfter) {
            return [
                'message' => 'Order status already set to: ' . $orderStatusAfter
            ];
        }

        $this->orderManagement->hold($order->getId());

        $this->updateOrderStatus(
            $order,
            $orderStatusAfter,
            \Magento\Sales\Model\Order::STATE_HOLDED
        );

        $this->logger->addInfo(
            "Hold order orderId: {$order->getIncrementId()}. qouteId: {$order->getQuoteId()} "
        );

        return [
            'order_status_before' => $orderStatusBefore,
            'order_status_after' => $this->orderManagement->getStatus($order->getId())
        ];
    }

    /**
     * Unholds the order if it is holded at put it backs in it previous state
     *
     * @param \Magento\Sales\Api\Data\OrderInterface $order
     */
    public function unHoldOrder(
        \Magento\Sales\Api\Data\OrderInterface $order
    ) {
        if (\Magento\Sales\Model\Order::STATE_HOLDED == $order->getState()) {
            $this->orderManagement->unHold($order->getId());
        }
    }

    /**
     * Cancels the order
     *
     * @param \Magento\Sales\Api\Data\OrderInterface  $order
     * @param \Webbhuset\CollectorCheckoutSDK\CheckoutData $checkoutData
     * @return array
     */
    public function cancelOrder(
        \Magento\Sales\Api\Data\OrderInterface $order,
        \Webbhuset\CollectorCheckoutSDK\CheckoutData $checkoutData
    ):array {
        $orderStatusBefore = $this->orderManagement->getStatus($order->getId());
        $orderStatusAfter  = $this->configFactory->create(['order' => $order])->getOrderStatusHolded();

        if ($orderStatusBefore == $orderStatusAfter) {
            return [
                'message' => 'Order status already set to: ' . $orderStatusAfter
            ];
        }

        $this->unHoldOrder($order);

        $this->orderManagement->cancel($order->getId());

        $this->logger->addInfo(
            "Cancel order orderId: {$order->getIncrementId()}. qouteId: {$order->getQuoteId()} "
        );

        $this->updateOrderStatus(
            $order,
            $this->configFactory->create(['order' => $order])->getOrderStatusDenied(),
            \Magento\Sales\Model\Order::STATE_CANCELED
        );

        return [
            'order_status_before' => $orderStatusBefore,
            'order_status_after' => $this->orderManagement->getStatus($order->getId())
        ];
    }

    /**
     * Invoices the order offline. This function is used when orders are autoactivated in Collector
     *
     * @param \Magento\Sales\Api\Data\OrderInterface  $order
     * @param \Webbhuset\CollectorCheckoutSDK\CheckoutData $checkoutData
     * @return array
     */
    public function activateOrder(
        \Magento\Sales\Api\Data\OrderInterface $order,
        \Webbhuset\CollectorCheckoutSDK\CheckoutData $checkoutData
    ):array {
        $orderStatusBefore = $this->orderManagement->getStatus($order->getEntityId());

        $this->acknowledgeOrder($order, $checkoutData);

        if (!$order->canInvoice()) {
            $this->logger->addInfo(
                "Could not create Magento invoice: {$order->getIncrementId()}. qouteId: {$order->getQuoteId()} "
            );
            return [
                'message' => 'Can not create invoice'
            ];
        }

        $this->updateOrderStatus(
            $order,
            \Magento\Sales\Model\Order::STATE_PROCESSING,
            \Magento\Sales\Model\Order::STATE_PROCESSING
        );

        $this->invoice->create()->invoiceOrderOffline($order);

        return [
            'order_status_before' => $orderStatusBefore,
            'order_status_after' => \Magento\Sales\Model\Order::STATE_PROCESSING
        ];
    }

    /**
     * Gets an order based on public token
     *
     * @param $publicToken
     * @return \Magento\Sales\Api\Data\OrderInterface
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function getOrderByPublicToken($publicToken): \Magento\Sales\Api\Data\OrderInterface
    {
        return $this->getColumnFromSalesOrder("collectorbank_public_id", $publicToken);
    }

    /**
     * Gets the pending orders that were create 48 hours ago or less
     *
     * @return array
     */
    public function getPendingCollectorBankOrders(): array
    {
        $ageInHours = \Webbhuset\CollectorCheckout\Gateway\Config::REMOVE_PENDING_ORDERS_HOURS;

        $pendingOrderStatus = $this->config->getOrderStatusNew();

        $to   = $this->dateTime->create()->gmtDate(null, "-$ageInHours hours");
        $from = $this->dateTime->create()->gmtDate(null, "-48 hours");

        $searchCriteria = $this->searchCriteriaBuilder
            ->addFilter('status', $pendingOrderStatus, 'eq')
            ->addFilter('created_at', $to, 'lt')
            ->addFilter('created_at', $from, 'gt')
            ->create();

        $pendingOrders = $this->orderRepository->getList($searchCriteria)->getItems();
        $pendingCollectorOrders = [];

        foreach ($pendingOrders as $order) {
            if ($order->getPayment()->getMethod() == \Webbhuset\CollectorCheckout\Gateway\Config::CHECKOUT_CODE) {
                $pendingCollectorOrders[] = $order;
            }
        }

        return $pendingCollectorOrders;
    }

    /**
     * Gets a the specified column from sales order table
     *
     * @param $column
     * @param $value
     * @return \Magento\Sales\Api\Data\OrderInterface
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    protected function getColumnFromSalesOrder($column, $value): \Magento\Sales\Api\Data\OrderInterface
    {
        $searchCriteria = $this->searchCriteriaBuilder
            ->addFilter($column, $value, 'eq')->create();
        $orderList = $this->orderRepository->getList($searchCriteria)->getItems();

        if (sizeof($orderList) == 0) {
            throw new \Magento\Framework\Exception\NoSuchEntityException();
        }

        return reset($orderList);
    }

    /**
     * Updates order status and state
     *
     * @param $order
     * @param $status
     * @param $state
     * @return $this
     */
    protected function updateOrderStatus($order, $status, $state)
    {
        $order->setState($state)
            ->setStatus($status);

        return $this;
    }

    /**
     * Adds payment information
     *
     * @param \Magento\Sales\Api\Data\OrderPaymentInterface $payment
     * @param \Webbhuset\CollectorCheckoutSDK\Checkout\Purchase  $purchaseData
     */
    protected function addPaymentInformation(
        \Magento\Sales\Api\Data\OrderPaymentInterface $payment,
        \Webbhuset\CollectorCheckoutSDK\Checkout\Purchase $purchaseData
    ) {
        $info = [
            'method_title'            => \Webbhuset\CollectorCheckout\Gateway\Config::PAYMENT_METHOD_NAME,
            'payment_name'            => $purchaseData->getPaymentName(),
            'amount_to_pay'           => $purchaseData->getAmountToPay(),
            'invoice_delivery_method' => $purchaseData->getInvoiceDeliveryMethod(),
            'purchase_identifier'     => $purchaseData->getPurchaseIdentifier()
        ];
        $payment->setAdditionalInformation($info);

        $payment->authorize(true, $purchaseData->getAmountToPay());
    }
}
