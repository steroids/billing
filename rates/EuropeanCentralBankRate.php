<?php

namespace steroids\billing\rates;

use steroids\billing\exceptions\CurrencyRateException;
use steroids\billing\models\BillingCurrency;
use steroids\billing\structure\CurrencyRates;
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
     * @var array
     */
    public array $currencyAliases = [
        self::CURRENCY_RUB => 'RUB',
        self::CURRENCY_EUR => 'EUR',
        self::CURRENCY_USD => 'USD',
    ];

    /**
     * @var string
     */
    public const URL = 'http://api.exchangeratesapi.io/v1/latest';

    /**
     * @inheritDoc
     */
    public function fetch()
    {
        // Send request:
        //   http://api.exchangeratesapi.io/v1/latest?access_key=europeanCentralBankApiKey&symbols=RUB,EUR,USD
        // Expected Response:
        //   {"success":true,"timestamp":1617689886,"base":"EUR","date":"2021-04-06","rates":{"RUB":90.181052,"EUR":1,"USD":1.18144}}
        $params = [
            'access_key' => $this->module->europeanCentralBankApiKey,
            'symbols' => implode(',', $this->currencyAliases),
        ];

        return self::getParseResponse($params);
    }

    protected static function getParseResponse($params)
    {
        $response = file_get_contents(self::URL . '?' . http_build_query($params));

        // Parse response
        $data = Json::decode($response);
        $rates = ArrayHelper::getValue($data, 'rates');
        if (!$rates) {
            throw new CurrencyRateException('Wrong api.exchangeratesapi.io response: ' . $response);
        }

        // Normalize values
        $currency = BillingCurrency::getByCode(self::CURRENCY_USD);

        return [
            self::CURRENCY_EUR => new CurrencyRates([
                'rateUsd' => $currency->amountToInt(round(
                    1 / (float)ArrayHelper::getValue($rates, 'USD'),
                    2
                ))
            ]),
            self::CURRENCY_RUB => new CurrencyRates([
                'rateUsd' => $currency->amountToInt(round(
                    (float)ArrayHelper::getValue($rates, 'RUB') / ArrayHelper::getValue($rates, 'USD'),
                    2
                ))
            ])
        ];
    }

    public static function testECB($customParams)
    {
        return self::getParseResponse($customParams);
    }
}
