<?php
declare(strict_types=1);

namespace Webbhuset\CollectorCheckout\Helper;

use Webbhuset\CollectorPaymentSDK\Invoice\Article\ArticleList as ArticleList;

class GetMatchingArticles
{
    private ProductType $productType;
    private Translation $translation;
    private GetSkuSuffix $skuSuffix;

    public function __construct(
        ProductType $productType,
        Translation  $translation,
        GetSkuSuffix $getSkuSuffix
    ) {
        $this->productType = $productType;
        $this->translation = $translation;
        $this->skuSuffix = $getSkuSuffix;
    }

    public function execute(
        ArticleList $matchingArticles,
        ArticleList $articleList,
        $invoice,
        \Magento\Sales\Api\Data\OrderInterface $order
    ) {
        $discountLabel = $this->translation->getLabelByStoreId("Discount", (int) $order->getStoreId());
        $this->skuSuffix->reset();

        /** @var \Magento\Sales\Model\Order\Invoice|\Magento\Sales\Model\Order\Creditmemo $invoice */
        foreach ($invoice->getAllItems() as $item) {
            $productType = $this->productType->getProductTypeById((int)$item->getProductId());
            $baseSku = $item->getSku();
            $article = null;
            $isBundleChild = $this->isBundleChild($item);

            if ($item->getQty() > 0) {
                if ($productType === \Magento\Catalog\Model\Product\Type::TYPE_BUNDLE) {
                    $skuSuffix = $this->skuSuffix->execute($baseSku);
                    $skuWithSuffix = $baseSku . $skuSuffix;

                    $article = $articleList->getAndRemoveArticleBySku($skuWithSuffix);
                    if (!$article && $skuSuffix) {
                        $article = $articleList->getAndRemoveArticleBySku($baseSku);
                    }
                } elseif ($isBundleChild) {
                    $skuSuffix = $this->skuSuffix->execute("- " . $baseSku);
                    $skuWithSuffix = "- " . $baseSku . $skuSuffix;

                    $article = $articleList->getAndRemoveArticleBySku($skuWithSuffix);
                    if (!$article && $skuSuffix) {
                        $article = $articleList->getAndRemoveArticleBySku("- " . $baseSku);
                    }
                } else {
                    $skuSuffix = $this->skuSuffix->execute($baseSku);
                    $skuWithSuffix = $baseSku . $skuSuffix;

                    $article = $articleList->getAndRemoveArticleBySku($skuWithSuffix);
                    if (!$article && $skuSuffix) {
                        $article = $articleList->getAndRemoveArticleBySku($baseSku);
                    }
                }

                if ($article) {
                    $article->setQuantity((int)$item->getQty());
                    $matchingArticles->addArticle($article);

                    $discountArticle = null;
                    $articleSku = $article->getSku();

                    if ($productType === \Magento\Catalog\Model\Product\Type::TYPE_BUNDLE) {
                        $discountArticle = $articleList->getAndRemoveDiscountArticleBySku($discountLabel . ": " . $articleSku);
                        if (!$discountArticle) {
                            $discountArticle = $articleList->getAndRemoveDiscountArticleBySku($discountLabel . ": " . $baseSku);
                        }
                    } elseif ($isBundleChild) {
                        $discountArticle = $articleList->getAndRemoveDiscountArticleBySku("- $discountLabel: " . $baseSku);
                        if (!$discountArticle && $skuSuffix) {
                            $discountArticle = $articleList->getAndRemoveDiscountArticleBySku("- $discountLabel: " . $baseSku . $skuSuffix);
                        }
                    } else {
                        $discountArticle = $articleList->getAndRemoveDiscountArticleBySku($baseSku . "-1");
                        if (!$discountArticle) {
                            $discountArticle = $articleList->getAndRemoveDiscountArticleBySku($discountLabel . ": " . $baseSku);
                        }
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

    private function isBundleChild($item): bool
    {
        $orderItem = $item->getOrderItem();
        if (!$orderItem) {
            return false;
        }
        $parentItem = $orderItem->getParentItem();
        if (!$parentItem) {
            return false;
        }
        return $parentItem->getProductType() === \Magento\Bundle\Model\Product\Type::TYPE_CODE;
    }
}
