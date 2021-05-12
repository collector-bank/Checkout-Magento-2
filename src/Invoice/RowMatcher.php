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

    /**
     * rowMatcher constructor.
     */
    public function __construct(
        \Webbhuset\CollectorCheckout\Data\OrderHandler $orderHandler,
        \Webbhuset\CollectorCheckout\Adapter $adapter,
        \Webbhuset\CollectorCheckout\Invoice\RowMatcher\CreditMemoHandler $creditMemoHandler,
        \Webbhuset\CollectorCheckout\Invoice\RowMatcher\InvoiceHandler $invoiceHandler
    ) {
        $this->orderHandler         = $orderHandler;
        $this->adapter              = $adapter;
        $this->creditMemoHandler    = $creditMemoHandler;
        $this->invoiceHandler       = $invoiceHandler;
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

        $matchingArticles = $this->invoiceHandler->addItemsAndDiscounts(
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

        $matchingArticles = $this->creditMemoHandler->addItemsAndDiscounts(
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
     * Convert adjustment fee to invoice rows
     *
     * @param $adjustmentFee
     * @return InvoiceRow
     */
    public function adjustmentToInvoiceRows(
        $adjustmentFee
    ): \Webbhuset\CollectorPaymentSDK\Invoice\Rows\InvoiceRow {
        if ($adjustmentFee > 0) {
            $articleId             = __('Adjustment Fee');
            $description     = __('Adjustment Fee');
        } else {
            $articleId             = __('Adjustment Refund');
            $description     = __('Adjustment Refund');
        }
        $vat = 0;
        $qty = 1;

        return new InvoiceRow($articleId, $description, $qty, $adjustmentFee, $vat);
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
        $checkoutData = $this->adapter->acquireCheckoutInformationFromOrder($order);
        $articles = new ArticleList();

        $items = $checkoutData->getOrder()->getItems();
        foreach ($items as $item) {
            /** @var $item \Webbhuset\CollectorCheckoutSDK\Checkout\Order\Item */
            $articleId      = $item->getId();
            $description    = $item->getDescription();
            $qty            = $item->getQuantity();
            $sku            = $item->getSku();
            $vat            = (float) $item->getVat();
            $unitPrice      = (float) $item->getUnitPrice();

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
}
