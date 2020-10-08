<?php

namespace steroids\billing\models;

use steroids\billing\BillingModule;
use steroids\billing\exceptions\BillingException;
use steroids\billing\models\meta\BillingCurrencyMeta;

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
        }

        foreach (static::find()->all() as $currency) {
            /** @var static $currency */
            static::$_instancesByCode[$currency->code] = $currency;
            static::$_instancesById[$currency->primaryKey] = $currency;
        }
    }

    /**
     * @param $fromCode
     * @param $toCode
     * @param $amount
     * @return int
     * @throws BillingException
     */
    public static function convert($fromCode, $toCode, $amount)
    {
        return static::getByCode($fromCode)->to($toCode, $amount);
    }

    /**
     * @param string $toCode
     * @param int|null $amount
     * @return int
     * @throws BillingException
     */
    public function to(string $toCode, int $amount = null)
    {
        return static::getByCode($toCode)->fromUsd($this->toUsd($amount));
    }

    /**
     * @param int|null $amount
     * @return int|null
     */
    public function toUsd(int $amount = null)
    {
        if ($this->code === static::USD) {
            return $amount;
        }

        return (int)round($amount * pow(10, $this->ratePrecision) / $this->rateUsd);
    }

    /**
     * @param int|null $amount
     * @return int|null
     */
    public function fromUsd(int $amount = null)
    {
        if ($this->code === static::USD) {
            return $amount;
        }

        return (int)round($amount * $this->rateUsd / pow(10, $this->ratePrecision));
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
     * @param array $rates Key-value array (code -> rawValue)
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
            $value = $currency->amountToInt($rates[$currency->code]);

            // Validate changes percent
            if (!$skipValidation && $currency->rateUsd) {
                $percent = round((abs($currency->rateUsd - $value) / $currency->rateUsd) * 100, 2);
                if ($percent > BillingModule::getInstance()->rateMaxDeviationPercent) {
                    \Yii::warning("Wrong rate value for currency {$currency->code}: {$currency->rateUsd} -> {$value} (deviation {$percent}%}");
                    $bool = false;
                    continue;
                }
            }

            $toUpdate[$currency->primaryKey] = $value;
        }

        // TODO Update in one request via INSERT + ON DUPLICATE UPDATE
        // Save in database
        foreach ($toUpdate as $id => $value) {
            static::updateAll(['rateUsd' => $value], ['id' => $id]);
        }

        return $bool;
    }

    public function amountToInt($value)
    {
        return round(floatval($value) * pow(10, $this->precision));
    }
}
