<?php


namespace Webbhuset\CollectorCheckout\Plugin;


class SubmitObserverBeforeExecute
{
    /**
     * @var \Webbhuset\CollectorCheckout\Data\OrderHandler
     */
    protected $orderHandler;

    /**
     * @param \Magento\Checkout\Model\Session $checkoutSession
     *
     * @codeCoverageIgnore
     */
    public function __construct(
        \Webbhuset\CollectorCheckout\Data\OrderHandler $orderHandler
    ) {
        $this->orderHandler = $orderHandler;
    }

    /**
     * @param \Magento\Sales\Model\Order\Email\Container\OrderIdentity $subject
     * @param callable $proceed
     * @return bool
     */
    public function beforeExecute($subject, $observer)
    {
        $order = $observer->getEvent()->getOrder();

        $publicToken = $this->orderHandler->getPublicToken($order);
        if ($publicToken) {
            $order->setCanSendNewEmailFlag(false);
        }

        return [$observer];
    }
}