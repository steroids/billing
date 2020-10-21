<?php

namespace steroids\billing;

use steroids\billing\operations\BaseBillingOperation;
use yii\base\Event;

class BillingExecuteEvent extends Event
{
    /**
     * @var BaseBillingOperation
     */
    public $sender;
}