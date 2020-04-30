<?php

namespace Webbhuset\CollectorCheckout\Checkout\Quote;

/**
 * Class Manager
 *
 * @package Webbhuset\CollectorCheckout\Checkout\Quote
 */
class Manager
{
    /**
     * @var \Magento\Framework\Api\SearchCriteriaBuilder
     */
    protected $searchCriteriaBuilder;
    /**
     * @var \Magento\Quote\Api\CartRepositoryInterface
     */
    protected $quoteRepository;
    /**
     * @var \Webbhuset\CollectorCheckout\Logger\Logger
     */
    protected $logger;

    /**
     * Manager constructor.
     *
     * @param \Magento\Framework\Api\SearchCriteriaBuilder   $searchCriteriaBuilder
     * @param \Magento\Quote\Api\CartRepositoryInterface     $quoteRepository
     * @param \Webbhuset\CollectorCheckout\Logger\Logger $logger
     */
    public function __construct(
        \Magento\Framework\Api\SearchCriteriaBuilder $searchCriteriaBuilder,
        \Magento\Quote\Api\CartRepositoryInterface $quoteRepository,
        \Webbhuset\CollectorCheckout\Logger\Logger $logger
    ) {
        $this->searchCriteriaBuilder = $searchCriteriaBuilder;
        $this->quoteRepository = $quoteRepository;
        $this->logger = $logger;
    }

    /**
     * Get quote by public token
     *
     * @param $publicToken
     * @return \Magento\Quote\Api\Data\CartInterface
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function getQuoteByPublicToken($publicToken): \Magento\Quote\Api\Data\CartInterface
    {
        return $this->getColumnFromQuote("collectorbank_public_id", $publicToken);
    }

    /**
     * Gets a the specified column from quote table
     *
     * @param $column
     * @param $value
     * @return \Magento\Quote\Api\Data\CartInterface
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    protected function getColumnFromQuote($column, $value): \Magento\Quote\Api\Data\CartInterface
    {
        $searchCriteria = $this->searchCriteriaBuilder
            ->addFilter($column, $value, 'eq')->create();

        $quoteList = $this->quoteRepository->getList($searchCriteria)->getItems();

        if (sizeof($quoteList) == 0) {
            $this->logger->addCritical("Could not find a quotes with column: : $column : value $value and quote-table");

            throw new \Magento\Framework\Exception\NoSuchEntityException();
        }

        return reset($quoteList);
    }
}
