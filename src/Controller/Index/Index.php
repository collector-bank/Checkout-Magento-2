<?php

namespace Webbhuset\CollectorCheckout\Controller\Index;

use Webbhuset\CollectorCheckout\Service\Quote\Initializer;
use Magento\Checkout\Model\Session;
use Magento\Framework\View\Result\PageFactory;
use Webbhuset\CollectorCheckout\Service\Checkout\Storage;

/**
 * Checkout index controller
 */
class Index extends \Magento\Framework\App\Action\Action
{
    /**
     * @var PageFactory
     */
    private PageFactory $pageFactory;

    /**
     * @var \Magento\Checkout\Model\Session
     */
    private Session $checkoutSession;

    /**
     * @var Initializer
     */
    private Initializer $quoteInitializer;

    /**
     * @var Storage
     */
    private Storage $storage;

    /**
     * Index constructor.
     *
     * @param \Magento\Framework\App\Action\Context                 $context
     * @param \Magento\Checkout\Model\Session                       $checkoutSession
     * @param \Magento\Framework\View\Result\PageFactory            $pageFactory
     * @param Initializer $quoteInitializer
     */
    public function __construct(
        \Magento\Framework\App\Action\Context $context,
        \Magento\Checkout\Model\Session $checkoutSession,
        \Magento\Framework\View\Result\PageFactory $pageFactory,
        Initializer $quoteInitializer,
        Storage $storage
    ) {
        $this->pageFactory      = $pageFactory;
        $this->checkoutSession  = $checkoutSession;
        $this->quoteInitializer = $quoteInitializer;
        $this->storage = $storage;

        parent::__construct($context);
    }

    /**
     * @return \Magento\Framework\App\ResponseInterface|\Magento\Framework\Controller\Result\Redirect|\Magento\Framework\Controller\ResultInterface|\Magento\Framework\View\Result\Page
     * @throws \Magento\Framework\Exception\LocalizedException
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function execute()
    {
        $page = $this->pageFactory->create();
        $quote = $this->checkoutSession->getQuote();
        $customerType = $this->getRequest()->getParam('customerType');
        $customerType = $customerType ? (int)$customerType : null;
        $initResult = $this->quoteInitializer->execute($quote, $customerType);

        if (Initializer::ERROR_CODE_CURRENCY === $initResult->getError()) {
            $this->messageManager->addErrorMessage(__('Currencies are not matching with what is allowed in Walley checkout'));
        }

        if (Initializer::ERROR_CODE_QUOTE === $initResult->getError()) {
            return $this->resultRedirectFactory->create()->setPath('checkout/index');
        }

        if ($initResult->getDeliveryCheckoutActive()) {
            $page->getConfig()->addBodyClass('delivery-checkout');
        }

        $this->storage->setPublicToken($initResult->getPublicToken());
        return $page;
    }
}
