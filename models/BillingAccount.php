<?php

namespace steroids\billing\models;

use steroids\billing\BillingModule;
use steroids\billing\models\meta\BillingAccountMeta;

class BillingAccount extends BillingAccountMeta
{
    /**
     * @inheritDoc
     */
    public static function instantiate($row)
    {
        return BillingModule::instantiateClass(static::class, $row);
    }
}
