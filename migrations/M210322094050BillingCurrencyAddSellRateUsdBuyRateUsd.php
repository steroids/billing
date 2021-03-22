<?php

namespace steroids\billing\migrations;

use steroids\core\base\Migration;

class M210322094050BillingCurrencyAddSellRateUsdBuyRateUsd extends Migration
{
    public function safeUp()
    {
        $this->addColumn('billing_currencies', 'sellRateUsd', $this->integer());
        $this->addColumn('billing_currencies', 'buyRateUsd', $this->integer());
    }

    public function safeDown()
    {
        $this->dropColumn('billing_currencies', 'sellRateUsd');
        $this->dropColumn('billing_currencies', 'buyRateUsd');
    }
}
