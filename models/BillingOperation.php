<?php

namespace steroids\billing\models;

use steroids\billing\BillingModule;
use steroids\billing\models\meta\BillingOperationMeta;

class BillingOperation extends BillingOperationMeta
{
    /**
     * @inheritDoc
     */
    public static function instantiate($row)
    {
        return BillingModule::instantiateClass(static::class, $row);
    }
}
