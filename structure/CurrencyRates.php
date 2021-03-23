<?php

namespace steroids\billing\structure;

use app\billing\enums\CurrencyEnum;
use steroids\billing\models\BillingCurrency;
use yii\base\BaseObject;

class CurrencyRates extends BaseObject
{
    public ?int $rateUsd = null;
    public ?int $sellRateUsd = null;
    public ?int $buyRateUsd = null;

}