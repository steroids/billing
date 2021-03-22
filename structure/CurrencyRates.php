<?php

namespace steroids\billing\structure;

use app\billing\enums\CurrencyEnum;
use steroids\billing\models\BillingCurrency;
use yii\base\BaseObject;

class CurrencyRates extends BaseObject
{
    public ?float $rateUsd = null;
    public ?float $sellRateUsd = null;
    public ?float $buyRateUsd = null;

}