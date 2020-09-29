<?php

namespace steroids\billing;

use steroids\billing\operations\BaseOperation;
use yii\base\Event;

class BillingExecuteEvent extends Event
{
    /**
     * @var BaseOperation
     */
    public $sender;
}