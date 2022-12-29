<?php
declare(strict_types=1);

namespace Webbhuset\CollectorCheckout\Api\Data;

interface IframeDataInterface
{
    const SRC = 'src';
    const DATA_TOKEN = 'data-token';
    const DATA_LANG = 'data-lang';
    const DATA_VERSION = 'data-version';
    const DATA_ACTION_COLOR = 'data-action-color';
    const DATA_ACTION_TEXT_COLOR = 'data-action-text-color';

    /**
     * @param string $src
     * @return \Webbhuset\CollectorCheckout\Api\Data\IframeDataInterface
     */
    public function setSrc(string $src):\Webbhuset\CollectorCheckout\Api\Data\IframeDataInterface;

    /**
     * @param string $dataToken
     * @return \Webbhuset\CollectorCheckout\Api\Data\IframeDataInterface
     */
    public function setDataToken(string $dataToken):\Webbhuset\CollectorCheckout\Api\Data\IframeDataInterface;

    /**
     * @param string $dataLang
     * @return \Webbhuset\CollectorCheckout\Api\Data\IframeDataInterface
     */
    public function setDataLang(string $dataLang):\Webbhuset\CollectorCheckout\Api\Data\IframeDataInterface;

    /**
     * @param string $dataVersion
     * @return \Webbhuset\CollectorCheckout\Api\Data\IframeDataInterface
     */
    public function setDataVersion(string $dataVersion):\Webbhuset\CollectorCheckout\Api\Data\IframeDataInterface;

    /**
     * @param string $dataActionColor
     * @return \Webbhuset\CollectorCheckout\Api\Data\IframeDataInterface
     */
    public function setDataActionColor(string $dataActionColor):\Webbhuset\CollectorCheckout\Api\Data\IframeDataInterface;

    /**
     * @param string $dataActionTextColor
     * @return \Webbhuset\CollectorCheckout\Api\Data\IframeDataInterface
     */
    public function setDataActionTextColor(string $dataActionTextColor):\Webbhuset\CollectorCheckout\Api\Data\IframeDataInterface;

    /**
     * @return string
     */
    public function getSrc():string;

    /**
     * @return string
     */
    public function getDataToken():string;

    /**
     * @return string
     */
    public function getDataLang():string;

    /**
     * @return string
     */
    public function getDataVersion():string;

    /**
     * @return string
     */
    public function getDataActionColor():string;

    /**
     * @return string
     */
    public function getDataActionTextColor():string;
}
