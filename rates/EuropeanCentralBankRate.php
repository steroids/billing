<?php

namespace steroids\billing\rates;

use steroids\billing\models\BillingCurrency;
use steroids\billing\structure\CurrencyRates;
use yii\base\Exception;
use yii\helpers\ArrayHelper;
use yii\helpers\Json;

/**
 * Class BaseRate
 * @package app\billing\rates
 */
class EuropeanCentralBankRate extends BaseRate
{
    /**
     * @var array
     */
    public array $currencyCodes = [
        self::CURRENCY_RUB,
        self::CURRENCY_EUR,
        self::CURRENCY_USD,
    ];

    /**
     * @var string
     */
    public string $url = 'http://api.exchangeratesapi.io/v1/latest';

    /**
     * @inheritDoc
     */
    public function fetch()
    {
        // Send request:
        //   http://api.exchangeratesapi.io/v1/latest?base=USD&symbols=RUB,EUR
        // Expected Response:
        //   {"rates": {"EUR":0.8457374831, "RUB":75.9667625169}, "base": "USD", "date": "2020-09-07"}
        $params = [
            'access_key' => $this->module->europeanCentralBankApiKey,
            //not support in base plan
//            'base' => $this->getAlias(self::CURRENCY_USD),
            'symbols' => implode(',', array_map(fn($code) => $this->getAlias($code), $this->currencyCodes)),
        ];
        $response = file_get_contents($this->url . '?' . http_build_query($params));

        // Parse response
        $data = Json::decode($response);
        $rates = ArrayHelper::getValue($data, 'rates');
        if (!$rates) {
            throw new Exception('Wrong api.exchangeratesapi.io response: ' . $response);
        }

        // Normalize values
        $currency = BillingCurrency::getByCode(self::CURRENCY_USD);
        return [
            self::CURRENCY_EUR => new CurrencyRates([
                'rateUsd' => $currency->amountToInt(round(
                    ArrayHelper::getValue($rates, 'EUR') / ArrayHelper::getValue($rates, 'USD'),
                    2
                ))
            ]),
            self::CURRENCY_RUB => new CurrencyRates([
                'rateUsd' => $currency->amountToInt(round(
                    1 / ArrayHelper::getValue($rates, 'USD'),
                    2
                ))
            ])
        ];
    }

    /**
     * @inheritDoc
     */
    public function getAlias($code)
    {
        return strtoupper(parent::getAlias($code));
    }
}
