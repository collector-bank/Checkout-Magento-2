<?php

namespace Webbhuset\CollectorCheckout\Controller\Notification;

/**
 * Class Index
 *
 * @package Webbhuset\CollectorCheckout\Controller\Notification
 */
class Index extends \Magento\Framework\App\Action\Action
{
    /**
     * @var \Webbhuset\CollectorCheckout\Checkout\Order\ManagerFactory
     */
    protected $orderManager;
    /**
     * @var \Magento\Framework\Controller\Result\JsonFactory
     */
    protected $jsonResult;

    /**
     * Index constructor.
     *
     * @param \Magento\Framework\App\Action\Context                          $context
     * @param \Webbhuset\CollectorCheckout\Checkout\Order\ManagerFactory $orderManager
     * @param \Magento\Framework\Controller\Result\JsonFactory               $jsonResult
     */
    public function __construct(
        \Magento\Framework\App\Action\Context $context,
        \Webbhuset\CollectorCheckout\Checkout\Order\ManagerFactory $orderManager,
        \Magento\Framework\Controller\Result\JsonFactory $jsonResult
    ) {
        $this->orderManager = $orderManager;
        $this->jsonResult   = $jsonResult;

        parent::__construct($context);
    }

    /**
     * @return \Magento\Framework\App\ResponseInterface|\Magento\Framework\Controller\Result\Json|\Magento\Framework\Controller\ResultInterface
     */
    public function execute()
    {
        $jsonResult = $this->jsonResult->create();
        $orderManager = $this->orderManager->create();

        $reference = $this->getRequest()->getParam('reference');
        try {
            $order = $this->orderManager->create()->getOrderByPublicToken($reference);
            $result = $orderManager->notificationCallbackHandler($order);

            $jsonResult->setHttpResponseCode(200);
            $jsonResult->setData($result);
        } catch (\Magento\Framework\Exception\NoSuchEntityException $e) {
            $jsonResult->setHttpResponseCode(200);
            $jsonResult->setData(['message' => __('Entity not found')]);

        } catch (\Throwable $e) {
            $jsonResult->setHttpResponseCode(200);
            $jsonResult->setData(['message' => __($e->getMessage())]);

        }
        return $jsonResult;
    }
}
