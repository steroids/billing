<?php

namespace steroids\billing\tests\unit;

use app\billing\enums\CurrencyEnum;
use app\billing\enums\SystemAccountName;
use app\billing\enums\UserAccountName;
use app\user\models\User;
use PHPUnit\Framework\TestCase;
use steroids\billing\BillingModule;
use steroids\billing\forms\ManualOperationForm;
use steroids\billing\models\BillingOperation;
use steroids\billing\operations\ManualOperation;
use steroids\billing\rates\EuropeanCentralBankRate;
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

        // Charge
        $comment = 'test' . microtime(true);
        $manualForm = new ManualOperationForm([
            'fromAccountName' => SystemAccountName::GATEWAY_MANUAL,
            'currencyCode' => CurrencyEnum::USD,
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

    public function testFetchECBRates()
    {
        $rates = EuropeanCentralBankRate::testECB(
            [
                'access_key' => (BillingModule::getInstance())->europeanCentralBankApiKey,
                'symbols' => implode(',', [
                    'rub' => 'RUB',
                    'eur' => 'EUR',
                    'usd' => 'USD'
                ]),
            ]);

        $this->assertArrayHasKey('eur', $rates);
        $this->assertArrayHasKey('rub', $rates);
    }
}
