<?php
declare(strict_types=1);

namespace Webbhuset\CollectorCheckout\Helper;

use Webbhuset\CollectorPaymentSDK\Invoice\Article\ArticleList as ArticleList;

class GetMatchingArticles
{
    private ProductType $productType;
    private Translation $translation;
    private GetSkuSuffix $getSkuSuffix;

    public function __construct(
        ProductType $productType,
        Translation  $translation,
        GetSkuSuffix $getSkuSuffix
    ) {
        $this->productType = $productType;
        $this->translation = $translation;
        $this->getSkuSuffix = $getSkuSuffix;
    }

    public function execute(
        ArticleList $matchingArticles,
        ArticleList $articleList,
        $invoice,
        \Magento\Sales\Api\Data\OrderInterface $order
    ) {
        $discountLabel = $this->translation->getLabelByStoreId("Discount", (int) $order->getStoreId());
        /** @var \Magento\Sales\Model\Order\Invoice|\Magento\Sales\Model\Order\Creditmemo $invoice */
        foreach ($invoice->getAllItems() as $item) {
            $productType = $this->productType->getProductTypeById((int)$item->getProductId());
            $sku = $item->getSku();
            $article = null;
            if ($item->getQty() > 0) {
                if ($productType === \Magento\Catalog\Model\Product\Type::TYPE_BUNDLE) {
                    $skuSuffix = $this->getSkuSuffix->execute($item->getSku());
                    if ($skuSuffix) {
                        $sku = $sku . $skuSuffix;
                    }
                }
                if ($item->getPrice() > 0) {
                    $article = $articleList->getArticleBySku($sku);
                }
                if (!$article) {
                    $article = $articleList->getArticleBySku("- " . $item->getSku());
                }
                if ($article) {
                    $article->setQuantity((int)$item->getQty());
                    $matchingArticles->addArticle($article);

                    if ($productType !== \Magento\Catalog\Model\Product\Type::TYPE_BUNDLE) {
                        $discountArticle = $articleList->getDiscountArticleBySku($item->getSku() . "-1");
                        if (!$discountArticle) {
                            $discountArticle = $articleList->getDiscountArticleBySku("- $discountLabel: " . $item->getSku());
                        }
                    } else {
                        $discountArticle = $articleList->getDiscountArticleBySku($discountLabel . ": " . $sku);
                    }

                    if ($discountArticle) {
                        $discountArticle->setQuantity((int) $item->getQty());
                        $matchingArticles->addArticle($discountArticle);
                    }
                }
            }
        }
        return $matchingArticles;
    }
}
