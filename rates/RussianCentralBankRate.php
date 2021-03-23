<?php


namespace steroids\billing\rates;


use Exception;
use steroids\billing\models\BillingCurrency;
use steroids\billing\structure\CurrencyRates;
use yii\helpers\ArrayHelper;
use yii\helpers\Json;

class RussianCentralBankRate extends BaseRate
{
    /**
     * @var array
     */
    public array $currencyCodes = [
        self::CURRENCY_RUB,
        self::CURRENCY_EUR,
    ];

    public string $url = 'https://www.cbr-xml-daily.ru/latest.js';

    /**
     * @inheritDoc
     */
    public function fetch()
    {
        $response = file_get_contents($this->url);

        // Parse response
        $data = Json::decode($response);
        $rates = ArrayHelper::getValue($data, 'rates');
        if (!$rates) {
            throw new Exception('Wrong api.exchangeratesapi.io response: ' . $response);
        }

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
}