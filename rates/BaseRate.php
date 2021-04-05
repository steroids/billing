<?php

namespace steroids\billing\rates;

use steroids\billing\BillingModule;
use yii\base\BaseObject;
use yii\helpers\ArrayHelper;

/**
 * Class BaseRate
 * @package app\billing\rates
 */
abstract class BaseRate extends BaseObject
{
    const CURRENCY_USD = 'usd';
    const CURRENCY_RUB = 'rub';
    const CURRENCY_EUR = 'eur';

    /**
     * @var array
     */
    public array $currencyCodes = [];

    /**
     * @var array
     */
    public array $currencyAliases = [];

    /**
     * Remote fetch rates
     * @return array Key-value rates (code -> value)
     */
    abstract public function fetch();

    /**
     * @param $code
     * @return mixed|null
     */
    public function getAlias($code)
    {
        return ArrayHelper::getValue($this->currencyAliases, $code, $code);
    }

    /**
     * @return \steroids\core\base\Module|BillingModule
     * @throws \yii\base\Exception
     */
    public function getModule()
    {
        return BillingModule::getInstance();
    }
}
