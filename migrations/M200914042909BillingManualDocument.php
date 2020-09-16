<?php

namespace steroids\billing\migrations;

use steroids\core\base\Migration;

class M200914042909BillingManualDocument extends Migration
{
    public function safeUp()
    {
        $this->createTable('billing_manual_documents', [
            'id' => $this->primaryKey(),
            'userId' => $this->integer(),
            'ipAddress' => $this->string(64),
            'comment' => $this->text(),
        ]);
    }

    public function safeDown()
    {
        $this->dropTable('billing_manual_documents');
    }
}
