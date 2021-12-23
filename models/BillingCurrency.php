<?php

namespace steroids\billing\models;

use steroids\billing\BillingModule;
use steroids\billing\enums\BillingCurrencyRateDirectionEnum;
use steroids\billing\exceptions\BillingException;
use steroids\billing\models\meta\BillingCurrencyMeta;
use steroids\billing\structure\CurrencyRates;
use steroids\payment\enums\PaymentDirection;

class BillingCurrency extends BillingCurrencyMeta
{
    const USD = 'usd';

    protected static ?array $_instancesByCode = null;
    protected static ?array $_instancesById = null;

    /**
     * @inheritDoc
     */
    public static function instantiate($row)
    {
        return BillingModule::instantiateClass(static::class, $row);
    }

    public static function asEnum($condition = [], $additionalFields = [], $onlyVisible = true)
    {
        $additionalFields = array_merge($additionalFields, [
            'code',
            'precision',
            'rateUsd',
            'ratePrecision',
        ]);
        return parent::asEnum($condition, $additionalFields, $onlyVisible);
    }

    /**
     * @return BillingCurrency[]
     */
    public static function getAll()
    {
        static::loadInstances();
        return array_values(static::$_instancesByCode);
    }

    /**
     * @param $code
     * @return BillingCurrency
     */
    public static function getByCode($code)
    {
        static::loadInstances();

        if (!isset(static::$_instancesByCode[$code])) {
            throw new BillingException('Not found currency by code: ' . $code);
        }
        return static::$_instancesByCode[$code];
    }

    /**
     * @param $id
     * @return BillingCurrency
     */
    public static function getById(int $id)
    {
        static::loadInstances();

        if (!isset(static::$_instancesById[$id])) {
            throw new BillingException('Not found currency by id: ' . $id);
        }
        return static::$_instancesById[$id];
    }

    public static function loadInstances()
    {
        if (!static::$_instancesByCode) {
            static::$_instancesByCode = [];
            static::$_instancesById = [];

            foreach (static::find()->all() as $currency) {
                /** @var static $currency */
                static::$_instancesByCode[$currency->code] = $currency;
                static::$_instancesById[$currency->primaryKey] = $currency;
            }
        }
    }

    /**
     * @param $fromCode
     * @param $toCode
     * @param $amount
     * @param BillingCurrencyRateDirectionEnum|null $rateDirection
     * @return int
     * @throws BillingException
     */
    public static function convert($fromCode, $toCode, $amount, $rateDirection = null)
    {
        if ($fromCode === $toCode) {
            return $amount;
        }

        return static::getByCode($fromCode)->to($toCode, $amount, $rateDirection);
    }

    /**
     * @param string $toCode
     * @param int|null $amount
     * @param BillingCurrencyRateDirectionEnum|null $rateDirection
     * @return int
     * @throws BillingException
     */
    public function to(string $toCode, int $amount = null, $rateDirection = null)
    {
        if ($this->code === $toCode) {
            return $amount;
        }

        return static::getByCode($toCode)
            ->fromUsd(
                $this->toUsd($amount, $rateDirection),
                $rateDirection
            );
    }

    /**
     * @param int|null $amountInt
     * @param BillingCurrencyRateDirectionEnum|null $rateDirection
     * @return int|null
     * @throws BillingException
     */
    public function toUsd(int $amountInt = null, $rateDirection = null)
    {
        if ($this->code === static::USD) {
            return $amountInt;
        }

        // Rate is stored in int, so converting it to float
        $rateFloat = $this->getUsdRateFloat($this->rateByDirection($rateDirection));

        $amountUsdFloat = $this->amountToFloat($amountInt) / $rateFloat;

        $amountUsdInt = static::getByCode(static::USD)->amountToInt($amountUsdFloat);

        return $amountUsdInt;
    }

    /**
     * @param int|null $amountInt
     * @param BillingCurrencyRateDirectionEnum|null $rateDirection
     * @return int|null
     * @throws BillingException
     */
    public function fromUsd(int $amountInt = null, $rateDirection = null)
    {
        if ($this->code === static::USD) {
            return $amountInt;
        }

        // Rate is stored in int, so converting it to float
        $rateFloat = $this->getUsdRateFloat($this->rateByDirection($rateDirection));

        $amountInContextCurrencyFloat = static::getByCode(static::USD)->amountToFloat($amountInt) * $rateFloat;

        $amountInContextCurrencyInt = $this->amountToInt($amountInContextCurrencyFloat);

        return $amountInContextCurrencyInt;
    }

    public function getUsdRateFloat(int $usdRateInt) {
        return $usdRateInt / pow(10, $this->ratePrecision);
    }

    /**
     * @param BillingCurrencyRateDirectionEnum|null $rateDirection
     * @return int
     */
    public function rateByDirection($rateDirection)
    {
        if ($rateDirection && $rateDirection === BillingCurrencyRateDirectionEnum::BUY) {
            return $this->buyRateUsd ?? $this->rateUsd;
        }

        if ($rateDirection && $rateDirection === BillingCurrencyRateDirectionEnum::SELL) {
            return $this->sellRateUsd ?? $this->rateUsd;
        }

        return $this->rateUsd;
    }

    /**
     * @param string $name
     * @param int|null $userId
     * @return BillingAccount
     * @throws BillingException
     * @throws \steroids\core\exceptions\ModelSaveException
     * @throws \yii\web\NotFoundHttpException
     */
    public function getAccount(string $name, int $userId = null)
    {
        return $userId
            ? BillingAccount::findOrCreate($name, $this->code, $userId)
            : BillingAccount::findSystem($name, $this->code);
    }

    /**
     * Update rates
     * @param array $rates Key-value array (code -> CurrencyRates)
     * @param bool $skipValidation Set true for skip deviation value validation and force update
     * @return bool
     * @throws \yii\base\Exception
     */
    public static function updateRates($rates, bool $skipValidation = false)
    {
        $bool = true;
        $toUpdate = [];

        $currencies = static::findAll(['code' => array_keys($rates)]);

        foreach ($currencies as $currency) {

            /**
             * @var CurrencyRates $currencyRates
             */
            $currencyRates = $rates[$currency->code];

            // Validate changes percent
            if (!$skipValidation) {
                foreach ($currencyRates as $attribute => $value) {
                    if (!$value || !$currency->$attribute) {
                        continue;
                    }

                    $percent = round((abs($currency->$attribute - $value) / $currency->$attribute) * 100, 2);
                    if ($percent > BillingModule::getInstance()->rateMaxDeviationPercent) {
                        \Yii::warning("Wrong rate value for currency {$currency->code}: {$currency->$attribute} -> {$value} (deviation {$percent}%}");
                        $bool = false;
                        continue 2;
                    }
                }
            }

            $toUpdate[$currency->primaryKey] = [
                'rateUsd' => $currencyRates->rateUsd ?? $currency->rateUsd,
                'sellRateUsd' => $currencyRates->sellRateUsd ?? $currency->sellRateUsd,
                'buyRateUsd' => $currencyRates->buyRateUsd ?? $currency->buyRateUsd,
                'updateTime' => (new \DateTime())->format('Y-m-d H:i:s')
            ];
        }

        // TODO Update in one request via INSERT + ON DUPLICATE UPDATE
        // Save in database
        foreach ($toUpdate as $id => $currencyRates) {
            static::updateAll($currencyRates, ['id' => $id]);
        }

        return $bool;
    }

    /**
     * @param $value
     * @return int|null
     */
    public function amountToInt($value)
    {
        if ($value === null) {
            return null;
        }
        return (int)round(floatval($value) * pow(10, $this->precision));
    }

    /**
     * @param $value
     * @return float|null
     */
    public function amountToFloat($value)
    {
        if ($value === null) {
            return null;
        }
        return round(floatval($value) / pow(10, $this->precision), $this->precision);
    }

    /**
     * @param int $amount
     * @return string
     */
    public function format($amount)
    {
        if ($amount === null) {
            return null;
        }
        return $this->amountToFloat($amount) . ' ' . $this->label;
    }

    /**
     * @param int $amount
     * @return int
     */
    public function zeroDecimalPart($amount)
    {
        return $this->amountToInt(floor($this->amountToFloat($amount)));
    }
}
