<?php

namespace Webbhuset\CollectorCheckout\Invoice;

use Webbhuset\CollectorPaymentSDK\Invoice\Article\Article as Article;
use Webbhuset\CollectorPaymentSDK\Invoice\Article\ArticleList as ArticleList;
use Webbhuset\CollectorPaymentSDK\Invoice\Rows\InvoiceRow;
use Webbhuset\CollectorPaymentSDK\Invoice\Rows\InvoiceRows;

/**
 * Class RowMatcher
 *
 * @package Webbhuset\CollectorCheckout\Invoice
 */
class RowMatcher
{
    /**
     * @var \Webbhuset\CollectorCheckout\Data\OrderHandler
     */
    protected $orderHandler;
    /**
     * @var \Webbhuset\CollectorCheckout\Adapter
     */
    protected $adapter;

    /**
     * @var RowMatcher\CreditMemoHandler
     */
    protected $creditMemoHandler;
    /**
     * @var RowMatcher\InvoiceHandler
     */
    protected $invoiceHandler;
    private \Webbhuset\CollectorCheckout\Test\GetOrderInformation $getOrderInformation;
    private \Webbhuset\CollectorCheckout\Helper\GetMatchingArticles $getMatchingArticles;

    /**
     * rowMatcher constructor.
     */
    public function __construct(
        \Webbhuset\CollectorCheckout\Data\OrderHandler $orderHandler,
        \Webbhuset\CollectorCheckout\Adapter $adapter,
        \Webbhuset\CollectorCheckout\Helper\GetMatchingArticles $getMatchingArticles,
        \Webbhuset\CollectorCheckout\Test\GetOrderInformation $getOrderInformation,
        \Webbhuset\CollectorCheckout\Invoice\RowMatcher\CreditMemoHandler $creditMemoHandler,
        \Webbhuset\CollectorCheckout\Invoice\RowMatcher\InvoiceHandler $invoiceHandler
    ) {
        $this->orderHandler         = $orderHandler;
        $this->adapter              = $adapter;
        $this->creditMemoHandler    = $creditMemoHandler;
        $this->invoiceHandler       = $invoiceHandler;
        $this->getOrderInformation  = $getOrderInformation;
        $this->getMatchingArticles  = $getMatchingArticles;
    }

    /**
     * Converts an invoice to a collector article list
     *
     * @param \Magento\Sales\Model\Order\Creditmemo  $creditMemo
     * @param \Magento\Sales\Api\Data\OrderInterface $order
     * @return ArticleList
     */
    public function invoiceToArticleList(
        \Magento\Sales\Model\Order\Invoice $invoice,
        \Magento\Sales\Api\Data\OrderInterface $order
    ): \Webbhuset\CollectorPaymentSDK\Invoice\Article\ArticleList {
        $checkoutDataArticleList = $this->checkoutDataToArticleList($order);

        $matchingArticles = new ArticleList();

        $matchingArticles = $this->getMatchingArticles->execute(
            $matchingArticles,
            $checkoutDataArticleList,
            $invoice,
            $order
        );

        $matchingArticles = $this->invoiceHandler->addShipping(
            $matchingArticles,
            $checkoutDataArticleList,
            $invoice,
            $order
        );

        $matchingArticles = $this->invoiceHandler->addDecimalRounding(
            $matchingArticles,
            $checkoutDataArticleList
        );

        return $matchingArticles;
    }

    /**
     * Returns the full article list from checkout data without doing any matching.
     * Used as a failsafe for full invoice activation to avoid complex matching edge cases.
     *
     * @param \Magento\Sales\Api\Data\OrderInterface $order
     * @return ArticleList
     */
    public function fullInvoiceToArticleList(
        \Magento\Sales\Api\Data\OrderInterface $order
    ): \Webbhuset\CollectorPaymentSDK\Invoice\Article\ArticleList {
        return $this->checkoutDataToArticleList($order);
    }

    /**
     * Converts a credit memo to a collector article list that can be used to credit items using collectors payment api
     *
     * @param \Magento\Sales\Model\Order\Creditmemo  $creditMemo
     * @param \Magento\Sales\Api\Data\OrderInterface $order
     * @return ArticleList
     */
    public function creditMemoToArticleList(
        \Magento\Sales\Model\Order\Creditmemo $creditMemo,
        \Magento\Sales\Api\Data\OrderInterface $order
    ): \Webbhuset\CollectorPaymentSDK\Invoice\Article\ArticleList {
        $checkoutDataArticleList = $this->checkoutDataToArticleList($order);

        $matchingArticles = new ArticleList();

        $matchingArticles = $this->getMatchingArticles->execute(
            $matchingArticles,
            $checkoutDataArticleList,
            $creditMemo,
            $order
        );
        $matchingArticles = $this->creditMemoHandler->addShipping(
            $matchingArticles,
            $checkoutDataArticleList,
            $creditMemo,
            $order
        );
        $matchingArticles = $this->creditMemoHandler->addDecimalRounding(
            $matchingArticles,
            $checkoutDataArticleList,
            $creditMemo,
            $order
        );


        return $matchingArticles;
    }

    /**
     * Returns the full article list from checkout data without doing any matching.
     * Used as a failsafe for full credit memo refund to avoid complex matching edge cases.
     *
     * @param \Magento\Sales\Api\Data\OrderInterface $order
     * @return ArticleList
     */
    public function fullCreditMemoToArticleList(
        \Magento\Sales\Api\Data\OrderInterface $order
    ): \Webbhuset\CollectorPaymentSDK\Invoice\Article\ArticleList {
        return $this->checkoutDataToArticleList($order);
    }

    /**
     * Get the checkout data for an order from collector and return an article list
     *
     * @param \Magento\Sales\Api\Data\OrderInterface $order
     * @return ArticleList
     */
    public function checkoutDataToArticleList(
        \Magento\Sales\Api\Data\OrderInterface $order
    ): \Webbhuset\CollectorPaymentSDK\Invoice\Article\ArticleList {
        $orderInformation = $this->getOrderInformation->execute((int)$order->getEntityId());
        $articles = new ArticleList();

        $items = $orderInformation['data']['items'];
        foreach ($items as $item) {
            /** @var $item \Webbhuset\CollectorCheckoutSDK\Checkout\Order\Item */
            $articleId      = $item['articleNumber'];
            $description    = $item['description'];
            $qty            = $item['quantity'];
            $sku            = $item['articleNumber'];
            $vat            = (float) $item['vatRate'];
            $unitPrice      = (float) $item['price'];

            $article = new Article($articleId, $description, $qty, $sku, $unitPrice, $vat);

            $articles->addArticle($article);
        }

        return $articles;
    }

    public function convertArticleListToInvoiceRows(
        ArticleList $articleList
    ): InvoiceRows {
        return new InvoiceRows();
    }

    /**
     * Convert adjustment fee to invoice rows
     *
     * @param $adjustmentFee
     * @return InvoiceRow
     */
    public function adjustmentToInvoiceRows(
        $adjustmentFee,
        $taxPercent = 0
    ): \Webbhuset\CollectorPaymentSDK\Invoice\Rows\InvoiceRow {
        if ($adjustmentFee > 0) {
            $articleId = __('Discount');
            $description = __('Discount');
            $type = 'discount';
        } else {
            $articleId  = __('Fee');
            $description = __('Fee');
            $type = 'fee';
        }
        $qty = 1;

        return new InvoiceRow($articleId, $description, $qty, $adjustmentFee, (float) $taxPercent, $type);
    }
}
