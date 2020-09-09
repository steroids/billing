<?php

namespace steroids\billing\models;

use steroids\billing\BillingModule;
use steroids\billing\models\meta\BillingCurrencyMeta;

class BillingCurrency extends BillingCurrencyMeta
{
    /**
     * @inheritDoc
     */
    public static function instantiate($row)
    {
        return BillingModule::instantiateClass(static::class, $row);
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
            $value = round(floatval($rates[$currency->code]) * pow(10, $currency->ratePrecision));

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
}
