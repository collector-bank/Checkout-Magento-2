<?php

namespace Webbhuset\CollectorCheckout\Config\Source\Country;

/**
 * Class Country
 *
 * @package Webbhuset\CollectorCheckout\Config\Source\Country
 */
class Country implements \Magento\Framework\Data\OptionSourceInterface
{
    /**
     * @var string Swedish country code
     */
    const SWEDEN  = "SE";
    /**
     * @var string Norweigan country code
     */
    const NORWAY  = "NO";
    /**
     * @var string Finish country code
     */
    const FINLAND = "FI";
    /**
     * @var string Danish country code
     */
    const DENMARK = "DK";
    /**
     * @var string German country code
     */
    const GERMANY = "DE";

    /**
     * Returns an array with country name per country code
     *
     * @return array
     */
    public function toOptionArray()
    {
        return [
            self::SWEDEN  => __('Sweden'),
            self::NORWAY  => __('Norway'),
            self::FINLAND => __('Finland'),
            self::DENMARK => __('Denmark'),
            self::GERMANY => __('Germany')
        ];
    }

    /**
     * Returns an array with currency per country code
     *
     * @return array
     */
    public function getCurrencyPerCountry()
    {
        return [
            self::SWEDEN  => 'SEK',
            self::NORWAY  => 'NOK',
            self::FINLAND => 'EUR',
            self::DENMARK => 'DKK',
            self::GERMANY => 'EUR'
        ];
    }

    /**
     * Returns an array with default language per country code
     *
     * @return array
     */
    public function getDefaultLanguagePerCounty()
    {
        return [
            self::SWEDEN  => 'sv-SE',
            self::NORWAY  => 'nb-NO',
            self::FINLAND => 'fi-FI',
            self::DENMARK => 'da-DK',
            self::GERMANY => 'en-DE'
        ];
    }
}
