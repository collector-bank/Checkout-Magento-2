<?php
declare(strict_types=1);

namespace Webbhuset\CollectorCheckout\Data;

use Magento\Sales\Api\OrderRepositoryInterface;

class ExtractWalleyOrderId
{
    /**
     * @var OrderRepositoryInterface
     */
    private OrderRepositoryInterface $orderRepository;

    public function __construct(
        OrderRepositoryInterface $orderRepository
    ) {
        $this->orderRepository = $orderRepository;
    }

    public function execute(int $orderId)
    {
        $order = $this->orderRepository->get($orderId);
        $additionalInformation = $order->getPayment()->getAdditionalInformation();
        $walleyOrderId = isset($additionalInformation['order_id']) ? $additionalInformation['order_id']: '';

        return $walleyOrderId;
    }
}
