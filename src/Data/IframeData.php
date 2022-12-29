<?php
declare(strict_types=1);

namespace Webbhuset\CollectorCheckout\Data;

use Magento\Framework\DataObject;
use Webbhuset\CollectorCheckout\Api\Data\IframeDataInterface;

class IframeData extends DataObject implements IframeDataInterface
{
    /**
     * @inheritDoc
     */
    public function setSrc(string $src): IframeDataInterface
    {
        return $this->setData(self::SRC, $src);
    }

    /**
     * @inheritDoc
     */
    public function setDataToken(string $token): IframeDataInterface
    {
        return $this->setData(self::DATA_TOKEN, $token);
    }

    /**
     * @inheritDoc
     */
    public function setDataLang(string $lang): IframeDataInterface
    {
        return $this->setData(self::DATA_LANG, $lang);
    }

    /**
     * @inheritDoc
     */
    public function setDataVersion(string $dataVersion): IframeDataInterface
    {
        return $this->setData(self::DATA_VERSION, $dataVersion);
    }

    /**
     * @inheritDoc
     */
    public function setDataActionTextColor(string $dataActionTextColor): IframeDataInterface
    {
        return $this->setData(self::DATA_ACTION_TEXT_COLOR, $dataActionTextColor);
    }

    /**
     * @inheritDoc
     */
    public function setDataActionColor(string $dataActionColor): IframeDataInterface
    {
        return $this->setData(self::DATA_ACTION_COLOR, $dataActionColor);
    }

    /**
     * @inheritDoc
     */
    public function getSrc(): string
    {
        return $this->getData(self::SRC) ?? '';
    }

    /**
     * @inheritDoc
     */
    public function getDataToken(): string
    {
        return $this->getData(self::DATA_TOKEN) ?? '';
    }

    /**
     * @inheritDoc
     */
    public function getDataLang(): string
    {
        return $this->getData(self::DATA_LANG) ?? '';
    }

    /**
     * @inheritDoc
     */
    public function getDataVersion(): string
    {
        return $this->getData(self::DATA_VERSION) ?? '';
    }

    /**
     * @inheritDoc
     */
    public function getDataActionColor(): string
    {
        return $this->getData(self::DATA_ACTION_COLOR) ?? '';
    }

    /**
     * @inheritDoc
     */
    public function getDataActionTextColor(): string
    {
        return $this->getData(self::DATA_ACTION_TEXT_COLOR) ?? '';
    }
}
