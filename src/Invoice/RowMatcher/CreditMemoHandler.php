<?php

namespace Webbhuset\CollectorCheckout\Invoice\RowMatcher;

use Webbhuset\CollectorCheckout\Helper\ProductType;
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
     * @var \Webbhuset\CollectorCheckout\Helper\Translation
     */
    protected $translation;
    /**
     * @var \Webbhuset\CollectorCheckout\Helper\GetSkuSuffix
     */
    protected $getSkuSuffix;

    /**
     * @var ProductType
     */
    protected $productType;

    /**
     * rowMatcher constructor.
     */
    public function __construct(
        \Webbhuset\CollectorCheckout\Data\OrderHandler $orderHandler,
        \Magento\Sales\Api\OrderItemRepositoryInterface $orderItemRepository,
        \Magento\Sales\Model\OrderRepository $orderRepository,
        \Webbhuset\CollectorCheckout\Helper\Translation $translation,
        ProductType $productType,
        \Webbhuset\CollectorCheckout\Helper\GetSkuSuffix $getSkuSuffix
    ) {
        $this->orderHandler         = $orderHandler;
        $this->orderItemRepository  = $orderItemRepository;
        $this->orderRepository      = $orderRepository;
        $this->translation          = $translation;
        $this->getSkuSuffix         = $getSkuSuffix;
        $this->productType          = $productType;
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
            $productType = $this->productType->getProductTypeById((int)$creditItem->getProductId());
            $sku = $creditItem->getSku();
            if ($creditItem->getQty() > 0) {
                if ($productType === \Magento\Catalog\Model\Product\Type::TYPE_BUNDLE) {
                    $skuSuffix = $this->getSkuSuffix->execute($creditItem->getSku());
                    if ($skuSuffix) {
                        $sku = $sku . $skuSuffix;
                    }
                }
                if ($creditItem->getPrice() > 0) {
                    $article = $articleList->getArticleBySku($sku);
                } else {
                    $article = $articleList->getArticleBySku("- " . $creditItem->getSku());
                }
                if ($article) {
                    $article->setQuantity($creditItem->getQty());
                    $matchingArticles->addArticle($article);

                    if ($productType !== \Magento\Catalog\Model\Product\Type::TYPE_BUNDLE) {
                        $discountArticle = $articleList->getDiscountArticleBySku($creditItem->getSku() . "-1");
                    } else {
                        $discountLabel = $this->translation->getLabelByStoreId("Discount", $order->getStoreId());
                        $discountArticle = $articleList->getDiscountArticleBySku($discountLabel . ": " . $sku);
                    }

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
