<?php
declare(strict_types=1);

namespace Webbhuset\CollectorCheckout\Test;

use Magento\Sales\Api\OrderRepositoryInterface;
use Webbhuset\CollectorCheckout\Adapter;
use Webbhuset\CollectorCheckout\Config\ConfigFactory;
use Webbhuset\CollectorCheckout\Invoice\RowMatcher;

class CancelInvoice
{
    private ConfigFactory $configFactory;
    /**
     * @var OrderRepositoryInterface
     */
    private OrderRepositoryInterface $orderRepository;
    /**
     * @var Adapter
     */
    private Adapter $adapter;
    /**
     * @var RowMatcher
     */
    private RowMatcher $rowMatcher;

    public function __construct(
        ConfigFactory $configFactory,
        OrderRepositoryInterface $orderRepository,
        RowMatcher $rowMatcher,
        Adapter $adapter
    ) {
        $this->configFactory = $configFactory;
        $this->orderRepository = $orderRepository;
        $this->adapter = $adapter;
        $this->rowMatcher = $rowMatcher;
    }

    public function execute(string $orderId)
    {
        $order = $this->orderRepository->get($orderId);
        $config = $this->configFactory->create(['order' => $order]);
        /** @var \Webbhuset\CollectorCheckoutSDK\Adapter\CurlWithAccessKey $adapter */
        $adapter = $this->adapter->getAdapter($config);
        $additionalInformation = $order->getPayment()->getAdditionalInformation();
        $walleyOrderId = $additionalInformation['order_id'];

        $articleList = $this->rowMatcher->checkoutDataToArticleList($order);
        $result = $adapter->cancelInvoice($walleyOrderId, $articleList, uniqid());

        return $result;
    }
}
