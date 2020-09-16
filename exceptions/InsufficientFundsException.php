<?php

namespace steroids\billing\exceptions;

class InsufficientFundsException extends BillingException
{
    public int $balance;
    public int $delta;

}