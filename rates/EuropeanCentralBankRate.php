<?php

namespace steroids\billing\rates;

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
    ];

    /**
     * @var string
     */
    public string $url = 'https://api.exchangeratesapi.io/latest';

    /**
     * @inheritDoc
     */
    public function fetch()
    {
        // Send request:
        //   https://api.exchangeratesapi.io/latest?base=USD&symbols=RUB,EUR
        // Expected Response:
        //   {"rates": {"EUR":0.8457374831, "RUB":75.9667625169}, "base": "USD", "date": "2020-09-07"}
        $params = [
            'base' => $this->getAlias(self::CURRENCY_USD),
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
        $result = [];
        foreach ($rates as $code => $value) {
            $result[strtolower($code)] = new CurrencyRates([
                'rateUsd' => round((float)$value, 2)
            ]);
        }

        return $result;
    }

    /**
     * @inheritDoc
     */
    public function getAlias($code)
    {
        return strtoupper(parent::getAlias($code));
    }
}
