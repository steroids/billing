<?php

namespace steroids\billing\tests\unit;

use app\billing\enums\CurrencyEnum;
use app\billing\enums\SystemAccountName;
use app\billing\enums\UserAccountName;
use app\user\models\User;
use PHPUnit\Framework\TestCase;
use steroids\billing\forms\ManualOperationForm;
use steroids\billing\models\BillingAccount;
use steroids\billing\models\BillingCurrency;
use steroids\billing\models\BillingOperation;
use steroids\billing\operations\ManualOperation;
use yii\helpers\Json;

class BillingTest extends TestCase
{
    public function testManualOperation()
    {
        $user = User::findOne(['id' => 1]);
        if (!$user) {
            $user = new User([
                'email' => 'test@test@example.com',
                'role' => 'user',
            ]);
            $user->saveOrPanic();
        }

        // Get system account
        $currency = BillingCurrency::getByCode(CurrencyEnum::USD);
        $fromAccount = BillingAccount::findOrCreate(SystemAccountName::GATEWAY_MANUAL, $currency->primaryKey);

        // Charge
        $comment = 'test' . microtime(true);
        $manualForm = new ManualOperationForm([
            'fromAccountName' => $fromAccount->name,
            'currencyId' => $fromAccount->currencyId,
            'toUserId' => $user->primaryKey,
            'toAccountName' => UserAccountName::MAIN,
            'amount' => 100,
            'comment' => $comment,
        ]);

        $manualForm->execute();
        $this->assertEquals('[]', Json::encode($manualForm->getErrors()));

        /** @var ManualOperation $manualOperation */
        $operation = BillingOperation::findOne(['id' => $manualForm->operation->primaryKey]);
        $this->assertEquals($comment, $operation->operation->document->comment);
    }

}
