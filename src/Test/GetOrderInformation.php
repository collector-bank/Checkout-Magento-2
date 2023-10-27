<?php
declare(strict_types=1);

namespace Webbhuset\CollectorCheckout\Test;

use Magento\Sales\Api\OrderRepositoryInterface;
use Webbhuset\CollectorCheckout\Adapter;
use Webbhuset\CollectorCheckout\Config\ConfigFactory;

class GetOrderInformation
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

    public function __construct(
        ConfigFactory $configFactory,
        OrderRepositoryInterface $orderRepository,
        Adapter $adapter
    ) {
        $this->configFactory = $configFactory;
        $this->orderRepository = $orderRepository;
        $this->adapter = $adapter;
    }

    public function execute(string $orderId)
    {
        $order = $this->orderRepository->get($orderId);
        $config = $this->configFactory->create(['order' => $order]);
        /** @var \Webbhuset\CollectorCheckoutSDK\Adapter\CurlWithAccessKey $adapter */
        $adapter = $this->adapter->getAdapter($config);
        $additionalInformation = $order->getPayment()->getAdditionalInformation();
        $walleyOrderId = $additionalInformation['order_id'];

        $result = $adapter->getOrder($walleyOrderId);

        return $result;
    }
}
