<?php

namespace steroids\billing\models;

use steroids\billing\BillingModule;
use steroids\billing\models\meta\BillingCurrencyMeta;
use yii\db\ActiveRecord;

class BillingCurrency extends BillingCurrencyMeta
{
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
     * @param $code
     * @return BillingCurrency|ActiveRecord|null
     */
    public static function getByCode($code)
    {
        return static::find()->where(['code' => $code])->limit(1)->one() ?: null;
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
        return round(floatval($value) * pow(10, $this->ratePrecision));
    }
}
