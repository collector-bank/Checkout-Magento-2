<?php

namespace Webbhuset\CollectorCheckout\Block\Admin;

use Webbhuset\CollectorCheckout\Config\Source\Customer\DefaultType as CustomerType;

/**
 * Class BusinessCustomer
 *
 * @package Webbhuset\CollectorCheckout\Block\Admin
 */
class BusinessCustomer extends \Magento\Backend\Block\Template
{
    /**
     * @var \Webbhuset\CollectorCheckout\Data\OrderHandler
     */
    protected $orderHandler;
    /**
     * @var \Magento\Framework\App\Request\Http
     */
    protected $request;
    /**
     * @var \Magento\Sales\Model\OrderRepository
     */
    protected $orderRepository;
    /**
     * @var \Magento\Sales\Api\Data\OrderInterface
     */
    protected $order;

    /**
     * BusinessCustomer constructor.
     *
     * @param \Magento\Backend\Block\Template\Context            $context
     * @param array                                              $data
     * @param \Webbhuset\CollectorCheckout\Data\OrderHandler $orderHandler
     * @param \Magento\Framework\App\Request\Http                $request
     * @param \Magento\Sales\Model\OrderRepository               $orderRepository
     */
    public function __construct(
        \Magento\Backend\Block\Template\Context $context,
        array $data = [],
        \Webbhuset\CollectorCheckout\Data\OrderHandler $orderHandler,
        \Magento\Framework\App\Request\Http $request,
        \Magento\Sales\Model\OrderRepository $orderRepository
    ) {
        parent::__construct($context, $data);

        $this->orderHandler    = $orderHandler;
        $this->request         = $request;
        $this->orderRepository = $orderRepository;
    }

    /**
     * Initiates the BusinessCustomer object
     */
    public function init()
    {
        $this->order = $this->loadOrder();
    }

    /**
     * Gets the collector reference / Public Token for the current order
     *
     * @return mixed|null
     */
    public function getReference()
    {
        return $this->orderHandler->getReference($this->order);
    }

    /**
     * Returns true if the customer is of type business customer
     *
     * @return bool
     */
    public function isBusinessCustomer()
    {
        $customerType = $this->orderHandler->getCustomerType($this->order);

        return CustomerType::BUSINESS_CUSTOMERS == $customerType;
    }

    /**
     * Returns the org number saved on the order
     *
     * @return mixed|null
     */
    public function getOrgNumber()
    {
        return $this->orderHandler->getOrgNumber($this->order);
    }

    /**
     * Returns the current order
     *
     * @return \Magento\Sales\Api\Data\OrderInterface
     * @throws \Magento\Framework\Exception\InputException
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    protected function loadOrder()
    {
        $orderId = $this->request->getParam('order_id');

        return $this->orderRepository->get($orderId);
    }
}
