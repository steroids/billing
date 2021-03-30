<?php

namespace steroids\billing\enums\meta;

use Yii;
use steroids\core\base\Enum;

abstract class BillingCurrencyRateDirectionEnumMeta extends Enum
{
    const SELL = 'sell';
    const BUY = 'buy';

    public static function getLabels()
    {
        return [
            self::SELL => Yii::t('app', 'Продажа'),
            self::BUY => Yii::t('app', 'Покупка')
        ];
    }
}
