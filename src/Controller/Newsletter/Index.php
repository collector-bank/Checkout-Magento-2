<?php

namespace Webbhuset\CollectorCheckout\Controller\Newsletter;

/**
 * Class Index
 *
 * @package Webbhuset\CollectorCheckout\Controller\Newsletter
 */
class Index extends \Magento\Framework\App\Action\Action
{
    /**
     * @var \Webbhuset\CollectorCheckout\Data\QuoteHandler
     */
    protected $quoteHandler;
    /**
     * @var \Magento\Checkout\Model\Session
     */
    protected $checkoutSession;
    /**
     * @var \Magento\Framework\Controller\Result\JsonFactory
     */
    protected $resultJsonFactory;
    /**
     * @var \Magento\Quote\Api\CartRepositoryInterface
     */
    protected $quoteRepository;

    /**
     * Index constructor.
     *
     * @param \Magento\Framework\App\Action\Context              $context
     * @param \Magento\Checkout\Model\Session                    $checkoutSession
     * @param \Webbhuset\CollectorCheckout\Data\QuoteHandler $quoteHandler
     * @param \Magento\Framework\Controller\Result\JsonFactory   $resultJsonFactory
     * @param \Magento\Quote\Api\CartRepositoryInterface         $quoteRepository
     */
    public function __construct(
        \Magento\Framework\App\Action\Context $context,
        \Magento\Checkout\Model\Session $checkoutSession,
        \Webbhuset\CollectorCheckout\Data\QuoteHandler $quoteHandler,
        \Magento\Framework\Controller\Result\JsonFactory $resultJsonFactory,
        \Magento\Quote\Api\CartRepositoryInterface $quoteRepository
    ) {
        $this->checkoutSession   = $checkoutSession;
        $this->quoteHandler      = $quoteHandler;
        $this->resultJsonFactory = $resultJsonFactory;
        $this->quoteRepository   = $quoteRepository;

        parent::__construct($context);
    }

    /**
     * @return \Magento\Framework\App\ResponseInterface|\Magento\Framework\Controller\Result\Json|\Magento\Framework\Controller\ResultInterface
     * @throws \Magento\Framework\Exception\LocalizedException
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function execute()
    {
        $quote = $this->checkoutSession->getQuote();
        $subscribe = ('true' == $this->getRequest()->getParam('subscribe')) ? 1 : 0;

        $this->quoteHandler->setNewsletterSubscribe($quote, $subscribe);
        $this->quoteRepository->save($quote);

        $result = $this->resultJsonFactory->create();
        $result->setData(
            [
                'newsletter' => $subscribe
            ]
        );

        return $result;
    }
}
