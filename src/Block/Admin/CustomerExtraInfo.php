<?php

namespace Webbhuset\CollectorCheckout\Block\Admin;

class CustomerExtraInfo
    extends \Magento\Backend\Block\Template
{
    protected $orderHandler;
    protected $request;
    protected $orderRepository;
    protected $order;

    public function __construct(
        \Magento\Backend\Block\Template\Context $context,
        \Webbhuset\CollectorCheckout\Data\OrderHandler $orderHandler,
        \Magento\Framework\App\Request\Http $request,
        \Magento\Sales\Model\OrderRepository $orderRepository,
        array $data = []
    ) {
        parent::__construct($context, $data);

        $this->orderHandler    = $orderHandler;
        $this->request         = $request;
        $this->orderRepository = $orderRepository;
    }

    public function init()
    {
        $this->order = $this->loadOrder();
    }

    public function getNationalIdentificationNumber()
    {
        return $this->orderHandler->getNationalIdentificationNumber($this->order);
    }

    protected function loadOrder()
    {
        $orderId = $this->request->getParam('order_id');

        return $this->orderRepository->get($orderId);
    }
}
