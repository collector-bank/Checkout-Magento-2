<?php

namespace Webbhuset\CollectorCheckout\Invoice\RowMatcher;

use Webbhuset\CollectorPaymentSDK\Invoice\Article\ArticleList as ArticleList;

class CreditMemoHandler
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
    public function addItemsAndDiscounts (
        ArticleList $matchingArticles,
        ArticleList $articleList,
        \Magento\Sales\Model\Order\Creditmemo $creditMemo,
        \Magento\Sales\Api\Data\OrderInterface $order
    ): ArticleList {
        foreach ($creditMemo->getAllItems() as $creditItem) {
            if ($creditItem->getQty() > 0) {
                $quoteId = $this->getItemQuoteIdBy($creditItem->getOrderItemId());

                $article = $articleList->getArticleBySku($quoteId);
                if($article) {
                    $article->setQuantity($creditItem->getQty());
                    $matchingArticles->addArticle($article);

                    $discountArticle = $articleList->getArticleBySku($quoteId . ":discount");
                    if ($discountArticle) {
                        $discountArticle->setQuantity($creditItem->getQty());
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
    public function addShipping (
        ArticleList $matchingArticles,
        ArticleList $articleList,
        \Magento\Sales\Model\Order\Creditmemo $creditMemo,
        \Magento\Sales\Api\Data\OrderInterface $order
    ): ArticleList {

        $orderShippingAmount = round($order->getShippingAmount(),2);
        $creditMemoShippingAmount = round($creditMemo->getShippingAmount(), 2);

        if ($creditMemoShippingAmount > 0
            && $orderShippingAmount != $creditMemoShippingAmount
        ) {
            throw new \Webbhuset\CollectorCheckout\Exception\Exception(
                __('Can only refund the whole shipping amount. Please use the merchant portal for other cases.')
            );
        }

        if ($creditMemoShippingAmount > 0
            && $orderShippingAmount == $creditMemoShippingAmount
        ) {
            $shippingArticle = $articleList->getShippingArticle();
            if($shippingArticle){
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
    public function addDecimalRounding (
        ArticleList $matchingArticles,
        ArticleList $articleList,
        \Magento\Sales\Model\Order\Creditmemo $creditMemo,
        \Magento\Sales\Api\Data\OrderInterface $order
    ): ArticleList {
        if (!$this->orderHandler->getDecimalRoundingCredited($order)) {
            $decimalRounding = $articleList->getDecimalRounding();
            if ($decimalRounding) {
                $this->orderHandler->setDecimalRoundingCredited($order);
                $this->orderRepository->save($order);
                $matchingArticles->addArticle($decimalRounding);
            }
        }

        return $matchingArticles;
    }


    /**
     * Get item from order from quote item id
     *
     * @param int $orderItemId
     * @return int|null
     */
    public function getItemQuoteIdBy (int $orderItemId)
    {
        $orderItem = $this->orderItemRepository->get($orderItemId);

        return $orderItem->getQuoteItemId();
    }
}