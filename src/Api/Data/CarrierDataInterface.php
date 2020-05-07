<?php

namespace Webbhuset\CollectorCheckout\Api\Data;

/**
 * Interface CarrierDataInterface
 *
 * @package Webbhuset\CollectorCheckout\Api\Data
 */
interface CarrierDataInterface
{
    /**
     * Gets data
     *
     * @return array
     */
    public function getData():array;

    /**
     * Sets data
     *
     * @param array $data
     * @return mixed
     */
    public function setData($data = []);
}