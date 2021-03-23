<?php


namespace steroids\billing\rates;


use Exception;
use steroids\billing\models\BillingCurrency;
use steroids\billing\structure\CurrencyRates;
use yii\helpers\ArrayHelper;
use yii\helpers\Json;

class TinkoffBankRate extends BaseRate
{
    const USD_CODE = 'USD';
    const EUR_CODE = 'EUR';
    const RUB_CODE = 'RUB';

    /**
     * @var array
     */
    protected array $currencyCodesMap = [
        self::EUR_CODE => self::CURRENCY_RUB,
        self::RUB_CODE => self::CURRENCY_EUR,
    ];

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
                $category['fromCurrency']['name'] !== self::USD_CODE ||
                !isset($this->currencyCodesMap[$category['toCurrency']['name']])
            ) {
                continue;
            }
            $ratesByCurrency[$this->currencyCodesMap[$category['toCurrency']['name']]] = [
                'buyRate' => $category['buy'],
                'sellRate' => $category['sell']
            ];
        }

        $currency = BillingCurrency::getByCode(self::CURRENCY_USD);
        $result = [];
        foreach ($this->currencyCodesMap as $companyCurrencyCode => $currencyCode) {
            $result[$currencyCode] = new CurrencyRates([
                'sellRateUsd' => $currency->amountToInt($ratesByCurrency[$currencyCode]['sellRate']),
                'buyRateUsd' => $currency->amountToInt($ratesByCurrency[$currencyCode]['buyRate']),
            ]);
        }

        return $result;
    }
}