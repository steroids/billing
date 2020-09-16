<?php

namespace steroids\billing\migrations;

use steroids\core\base\Migration;

class M000000200000Billing extends Migration
{
    public function safeUp()
    {
        $this->createTable('billing_accounts', [
            'id' => $this->primaryKey(),
            'name' => $this->string(32)->notNull(),
            'currencyId' => $this->integer()->notNull(),
            'userId' => $this->integer(),
            'balance' => $this->bigInteger()->notNull()->defaultValue(0),
        ]);
        $this->createTable('billing_currencies', [
            'id' => $this->primaryKey(),
            'code' => $this->string(32)->notNull(),
            'precision' => $this->integer()->notNull(),
            'label' => $this->string(),
            'rateUsd' => $this->bigInteger(),
            'ratePrecision' => $this->integer()->notNull(),
            'isVisible' => $this->boolean()->notNull()->defaultValue(false),
            'createTime' => $this->dateTime(),
            'updateTime' => $this->dateTime(),
        ]);
        $this->createTable('billing_operations', [
            'id' => $this->primaryKey(),
            'name' => $this->string()->notNull(),
            'currencyId' => $this->integer()->notNull(),
            'fromAccountId' => $this->integer()->notNull(),
            'toAccountId' => $this->integer()->notNull(),
            'documentId' => $this->integer(),
            'delta' => $this->bigInteger(),
            'createTime' => $this->dateTime(),
        ]);
    }

    public function safeDown()
    {
        $this->dropTable('billing_accounts');
        $this->dropTable('billing_currencies');
        $this->dropTable('billing_operations');
    }
}
