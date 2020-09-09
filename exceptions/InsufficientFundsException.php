<?php

namespace steroids\billing\exceptions;

class InsufficientFundsException extends BillingException {

    public $balance;
    public $delta;

}