<?php

namespace Webbhuset\CollectorCheckout\Checkout\Order;

use Magento\Sales\Api\Data\OrderInterface;
use Webbhuset\CollectorCheckout\Config\OrderConfig;
use Webbhuset\CollectorCheckoutSDK\Checkout\Purchase\Result as PurchaseResult;
use Webbhuset\CollectorCheckoutSDK\CheckoutData;
use Magento\Checkout\Model\Session as CheckoutSession;

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
     * @var SetOrderStatus
     */
    private $setOrderStatus;
    private $subscriptionManager;
    private $newsletterModel;
    private \Magento\Newsletter\Model\Subscriber $newsletterSubscriber;

    /**
     * @var CheckoutSession
     */
    protected $checkoutSession;

    public function __construct(
        \Magento\Quote\Api\CartManagementInterface $cartManagement,
        \Magento\Sales\Model\OrderRepository $orderRepository,
        \Webbhuset\CollectorCheckout\Data\OrderHandler $orderHandler,
        \Magento\Framework\Api\SearchCriteriaBuilder $searchCriteriaBuilder,
        \Webbhuset\CollectorCheckout\AdapterFactory $collectorAdapter,
        \Magento\Sales\Api\OrderManagementInterface $orderManagement,
        \Webbhuset\CollectorCheckout\Checkout\Order\SetOrderStatus $setOrderStatus,
        \Webbhuset\CollectorCheckout\Config\OrderConfigFactory $configFactory,
        \Magento\Quote\Model\QuoteManagement $quoteManagement,
        \Webbhuset\CollectorCheckout\Checkout\Order\ManagerFactory $orderManager,
        \Magento\Framework\Registry $registry,
        \Magento\Framework\Stdlib\DateTime\DateTimeFactory $dateTime,
        \Webbhuset\CollectorCheckout\Invoice\AdministrationFactory $invoice,
        \Magento\Newsletter\Model\Subscriber $newsletterSubscriber,
        \Webbhuset\CollectorCheckout\Logger\Logger $logger,
        \Magento\Newsletter\Model\SubscriberFactory $subscriberFactory,
        \Webbhuset\CollectorCheckout\Config\Config $config,
        \Webbhuset\CollectorCheckout\Carrier\Manager $carrierManager,
        CheckoutSession $checkoutSession
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
        $this->setOrderStatus        = $setOrderStatus;
        $this->newsletterSubscriber  = $newsletterSubscriber;
        $this->checkoutSession  = $checkoutSession;
    }

    /**
     * Create order from quote and return increment order id
     *
     * @param \Magento\Quote\Model\Quote $quote
     * @return string incrementOrderId
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

        if (!$this->checkoutSession->getLastOrderId()) {
            $this->checkoutSession
                ->setLastQuoteId($quoteId)
                ->setLastSuccessQuoteId($quoteId)
                ->setLastOrderId($orderId)
                ->setLastRealOrderId($incrementOrderId)
                ->setLastOrderStatus($order->getStatus());
        }

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
        if (!$this->registry->registry('isSecureArea')) {
            $this->registry->register('isSecureArea', 'true');
        }

        $this->orderRepository->delete($order);
        $this->logger->addInfo(
            "Delete order {$order->getIncrementId()}. qouteId: {$order->getQuoteId()} "
        );

        $this->registry->unregister('isSecureArea');
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
                $this->removeAndCancelOrder($order);
            }
            if (\Magento\Sales\Model\Order::STATE_CANCELED == $order->getState()) {
                $this->deleteOrder($order);
            }
        } catch (\Magento\Framework\Exception\NoSuchEntityException $e) {
        }
    }

    /**
     * Removes the order if it exists
     *
     * @param \Magento\Sales\Api\Data\OrderInterface $order
     */
    public function removeAndCancelOrder(\Magento\Sales\Api\Data\OrderInterface $order)
    {
        try {
            if (!$this->cancelOrderAndLog($order)) {
                return false;
            }

            $this->deleteOrder($order);
        } catch (\Magento\Framework\Exception\NoSuchEntityException $e) {
            return false;
        }

        return true;
    }

    /**
     * Cancel and log in collector log
     *
     * @param \Magento\Sales\Api\Data\OrderInterface $order
     * @return bool
     */
    public function cancelOrderAndLog(\Magento\Sales\Api\Data\OrderInterface $order): bool
    {
        try {
            $cancelSuccess = $this->orderManagement->cancel((int) $order->getId());

            if (!$cancelSuccess) {
                $this->logger->addCritical(
                    "Failed to cancel the order: {$order->getIncrementId()}. qouteId: {$order->getQuoteId()} "
                );
                return false;
            }
            $this->logger->addInfo(
                "Order is cancelled: {$order->getIncrementId()}. qouteId: {$order->getQuoteId()} "
            );
        } catch (\Magento\Framework\Exception\NoSuchEntityException $e) {
            return false;
        }

        return true;
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

        $config = $this->configFactory->create(['order' => $order]);
        $checkoutData = $checkoutAdapter->acquireCheckoutInformation($config, $collectorBankPrivateId);

        $paymentResult = $checkoutData->getPurchase()->getResult()->getResult();

        $result = "";
        switch ($paymentResult) {
            case PurchaseResult::PRELIMINARY:
                $result = $this->acknowledgeOrder($order, $checkoutData);
                if (isset($result['order_status_before']) && $result['order_status_before'] !== $result['order_status_after']) {
                    $this->orderRepository->save($order);
                    $this->saveAdditionalData($order, $checkoutData, $config);
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

            case PurchaseResult::COMPLETED:
                $this->saveAdditionalData($order, $checkoutData, $config);
                $result = $this->completeOrder($order, $checkoutData);
                break;
        }

        return $result;
    }

    public function saveAdditionalData(OrderInterface $order, CheckoutData $checkoutData, OrderConfig $config)
    {
        if ($config->getIsDeliveryCheckoutActive()) {
            $this->carrierManager->saveShipmentDataOnOrder($order->getId(), $checkoutData);
        }
        if ($config->isNewsletter()) {
            $newsletterField = $checkoutData->getCustomFieldNewsletter();
            if (!empty($newsletterField) && isset($newsletterField['value']) && $newsletterField['value']) {
                $this->subscribe($order);
            }
        }
        if ($config->isComment()) {
            $commentField = $checkoutData->getCustomFieldComment();
            if (!empty($commentField) && isset($commentField['value']) && strlen($commentField['value']) > 0) {
                $order->addCommentToStatusHistory($commentField['value']);
            }
        }
    }

    public function subscribe(OrderInterface $order)
    {
        $this->newsletterSubscriber->subscribe($order->getCustomerEmail());
    }

    /**
     * Acknowledged orders by adding payment information and changes state to processing
     *
     * @param \Magento\Sales\Api\Data\OrderInterface  $order
     * @param CheckoutData $checkoutData
     * @return array
     * @throws \Exception
     */
    public function acknowledgeOrder(
        \Magento\Sales\Api\Data\OrderInterface $order,
        CheckoutData $checkoutData
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
        try {
            $this->orderManagement->notify($order->getEntityId());
        } catch (\Exception $e) {

        }


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
     * @param CheckoutData $checkoutData
     * @return array
     */
    public function holdOrder(
        \Magento\Sales\Api\Data\OrderInterface $order,
        CheckoutData $checkoutData
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
     * @param CheckoutData $checkoutData
     * @return array
     */
    public function cancelOrder(
        \Magento\Sales\Api\Data\OrderInterface $order,
        CheckoutData $checkoutData
    ):array {
        $orderStatusBefore = $this->orderManagement->getStatus($order->getId());
        $orderStatusAfter  = $this->configFactory->create(['order' => $order])->getOrderStatusDenied();

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
     * @param CheckoutData $checkoutData
     * @return array
     */
    public function activateOrder(
        \Magento\Sales\Api\Data\OrderInterface $order,
        CheckoutData $checkoutData
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
     * Called on swishorders that are already completed when they are placed.
     * Invoices the order offline
     *
     * @param \Magento\Sales\Api\Data\OrderInterface  $order
     * @param CheckoutData $checkoutData
     * @return array
     */
    public function completeOrder(
        \Magento\Sales\Api\Data\OrderInterface $order,
        CheckoutData $checkoutData
    ):array {
        return $this->activateOrder($order, $checkoutData);
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
     * @return \Magento\Sales\Api\Data\OrderInterface[]
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
                $additional = $order->getPayment()->getAdditionalInformation();
                if ($additional
                    && is_array($additional)
                    && isset($additional['purchase_identifier'])
                ) {
                    continue;
                }

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
     * @param OrderInterface $order
     * @param string $status
     * @param string $state
     * @return $this
     */
    public function updateOrderStatus(OrderInterface $order,string $status,string $state)
    {
        $this->setOrderStatus->execute(
            (int) $order->getEntityId(),
            $status,
            $state
        );
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
            'purchase_identifier'     => $purchaseData->getPurchaseIdentifier(),
            'order_id'                => $purchaseData->getOrderId(),
        ];
        $payment->setAdditionalInformation($info);

        $payment->authorize(true, $payment->getBaseAmountOrdered());
    }
}
