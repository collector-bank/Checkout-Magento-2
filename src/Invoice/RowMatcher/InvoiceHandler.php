<?php

namespace Webbhuset\CollectorCheckout\Invoice\RowMatcher;

use Webbhuset\CollectorPaymentSDK\Invoice\Article\ArticleList as ArticleList;

class InvoiceHandler
{
    /**
     * @var \Webbhuset\CollectorCheckout\Data\OrderHandler
     */
    protected $orderHandler;
    /**
     * @var \Magento\Sales\Api\OrderItemRepositoryInterface
     */
    protected $orderItemRepository;

    /**
     * @var \Magento\Sales\Model\OrderRepository
     */
    protected $orderRepository;
    /**
     * rowMatcher constructor.
     */
    public function __construct(
        \Webbhuset\CollectorCheckout\Data\OrderHandler $orderHandler,
        \Webbhuset\CollectorCheckout\Adapter $adapter,
        \Magento\Sales\Api\OrderItemRepositoryInterface $orderItemRepository,
        \Magento\Sales\Model\OrderRepository $orderRepository
    ) {
        $this->orderHandler         = $orderHandler;
        $this->orderItemRepository  = $orderItemRepository;
        $this->orderRepository      = $orderRepository;
    }

    /**
     *
     * Add items and discount as matchingArticles
     *
     * @param ArticleList                            $matchingArticles
     * @param ArticleList                            $articleList
     * @param \Magento\Sales\Model\Order\Creditmemo  $creditMemo
     * @param \Magento\Sales\Api\Data\OrderInterface $order
     * @return ArticleList
     */
    public function addItemsAndDiscounts(
        ArticleList $matchingArticles,
        ArticleList $articleList,
        \Magento\Sales\Model\Order\Invoice $invoice,
        \Magento\Sales\Api\Data\OrderInterface $order
    ): ArticleList {
        foreach ($invoice->getAllItems() as $invoiceItem) {
            if ($invoiceItem->getQty() > 0) {
                $quoteId = $this->getItemQuoteIdBy($invoiceItem->getOrderItemId());

                $article = $articleList->getArticleBySku($invoiceItem->getSku());
                if ($article) {
                    $article->setQuantity($invoiceItem->getQty());
                    $matchingArticles->addArticle($article);

                    $discountArticle = $articleList->getArticleBySku($quoteId . ":discount");
                    if ($discountArticle) {
                        $discountArticle->setQuantity($invoiceItem->getQty());
                        $matchingArticles->addArticle($discountArticle);
                    }
                }
            }
        }
        return $matchingArticles;
    }

    /**
     *
     * Add shipping as matchingArticles
     *
     * @param ArticleList                            $matchingArticles
     * @param ArticleList                            $articleList
     * @param \Magento\Sales\Model\Order\Creditmemo  $creditMemo
     * @param \Magento\Sales\Api\Data\OrderInterface $order
     * @return ArticleList
     * @throws \Webbhuset\CollectorCheckout\Exception\Exception
     */
    public function addShipping(
        ArticleList $matchingArticles,
        ArticleList $articleList,
        \Magento\Sales\Model\Order\Invoice $invoice,
        \Magento\Sales\Api\Data\OrderInterface $order
    ): ArticleList {
        $invoiceShippingAmount = $invoice->getShippingAmount();

        if ($invoiceShippingAmount >= 0
            && !$order->getPayment()->getShippingCaptured()
        ) {
            $shippingArticle = $articleList->getShippingArticle();
            if ($shippingArticle) {
                $matchingArticles->addArticle($shippingArticle);
            }
        }

        return $matchingArticles;
    }

    /**
     *
     * Add decimal rounding to matchingArticles
     *
     * @param ArticleList                            $matchingArticles
     * @param ArticleList                            $articleList
     * @param \Magento\Sales\Model\Order\Creditmemo  $creditMemo
     * @param \Magento\Sales\Api\Data\OrderInterface $order
     * @return ArticleList
     */
    public function addDecimalRounding(
        ArticleList $matchingArticles,
        ArticleList $articleList
    ): ArticleList {
        $decimalRounding = $articleList->getArticleBySku(\Webbhuset\CollectorCheckout\Gateway\Config::CURRENCY_ROUNDING_SKU);

        if ($decimalRounding) {
            $matchingArticles->addArticle($decimalRounding);
        }

        return $matchingArticles;
    }

    /**
     * isDecimalRoundingInvoiced checks if decimal rounding has been invoiced on the invoice
     *
     * @param \Magento\Sales\Api\Data\OrderInterface $order
     * @return bool
     */
    public function isDecimalRoundingInvoiced(
        \Magento\Sales\Api\Data\OrderInterface $order
    ) {
        return $this->orderHandler->getDecimalRoundingInvoiced($order);
    }

    /**
     * setDecimalRoundingIsInvoiced set on the order that decimal rounding has been invoiced
     *
     * @param \Magento\Sales\Api\Data\OrderInterface $order
     */
    public function setDecimalRoundingIsInvoiced(
        \Magento\Sales\Api\Data\OrderInterface $order
    ):void {
        $this->orderHandler->setDecimalRoundingInvoiced($order);
        $this->orderRepository->save($order);
    }

    /**
     * Get item from order from quote item id
     *
     * @param int $orderItemId
     * @return int|null
     */
    public function getItemQuoteIdBy(int $orderItemId)
    {
        $orderItem = $this->orderItemRepository->get($orderItemId);

        return $orderItem->getQuoteItemId();
    }
}
