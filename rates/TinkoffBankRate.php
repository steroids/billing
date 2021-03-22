<?php


namespace steroids\billing\rates;


use Exception;
use steroids\billing\structure\CurrencyRates;
use yii\helpers\ArrayHelper;
use yii\helpers\Json;

class TinkoffBankRate extends BaseRate
{
    /**
     * @var array
     */
    public array $currencyCodes = [
        self::CURRENCY_RUB,
        self::CURRENCY_EUR,
    ];

    public $url = 'https://www.tinkoff.ru/api/v1/currency_rates/';

    private $categoryName = 'SMETransferAbove100';

    /**
     * @inheritDoc
     */
    public function fetch()
    {
        $response = file_get_contents($this->url);

        // Parse response
        $data = Json::decode($response);
        $ratesByCategories = ArrayHelper::getValue($data, 'payload.rates');
        if (!$ratesByCategories) {
            throw new Exception('Wrong api.exchangeratesapi.io response: ' . $response);
        }

        $ratesByCurrency = [];
        foreach ($ratesByCategories as $category) {
            if (
                $category['category'] !== $this->categoryName ||
                !isset($category['sell']) ||
                !isset($category['buy']) ||
                $category['fromCurrency']['name'] !== 'USD'
            ) {
                continue;
            }

            $ratesByCurrency[mb_strtolower($category['toCurrency']['name'])] = [
                'buyRate' => $category['buy'],
                'sellRate' => $category['sell']
            ];
        }

        $result = [];
        foreach ($this->currencyCodes as $currencyCode) {
            $result[$currencyCode] = new CurrencyRates([
                'sellRateUsd' => $ratesByCurrency[$currencyCode]['sellRate'],
                'buyRateUsd' => $ratesByCurrency[$currencyCode]['buyRate'],
            ]);
        }

        return $result;
    }
}