<?php

namespace steroids\billing\forms;

use steroids\billing\BillingModule;
use steroids\billing\exceptions\InsufficientFundsException;
use steroids\billing\forms\meta\ManualOperationFormMeta;
use steroids\billing\models\BillingAccount;
use steroids\billing\models\BillingCurrency;
use steroids\billing\models\BillingManualDocument;
use steroids\billing\models\BillingOperation;
use steroids\billing\operations\BaseOperation;
use steroids\billing\operations\ManualOperation;

/**
 * Class ManualOperationForm
 * @package steroids\billing\forms
 */
class ManualOperationForm extends ManualOperationFormMeta
{
    public ?int $userId = null;
    public ?string $ipAddress = null;

    public ?BillingOperation $operation;

    /**
     * @throws \steroids\core\exceptions\ModelSaveException
     * @throws \yii\base\Exception
     * @throws \yii\web\NotFoundHttpException
     */
    public function execute()
    {
        if ($this->validate()) {
            // Get accounts
            /** @var BillingAccount $accountClass */
            $accountClass = BillingModule::resolveClass(BillingAccount::class);
            $fromAccount = $accountClass::findSystem($this->fromAccountName, $this->currencyCode);
            $toAccount = $accountClass::findOrCreate($this->toAccountName, $this->currencyCode, $this->toUserId);

            // Create operation
            $operation = new ManualOperation([
                'fromAccount' => $fromAccount,
                'toAccount' => $toAccount,
                'amount' => $toAccount->currency->amountToInt($this->amount),
                'document' => [
                    'userId' => $this->userId,
                    'ipAddress' => $this->ipAddress,
                    'comment' => $this->comment,
                ],
            ]);

            // Execute operation
            try {
                $operation->execute();
            } catch (InsufficientFundsException $e) {
                $this->addError('amount', \Yii::t('app', 'На балансе недостаточно средств: {amount}', [
                    'amount' => $e->balance,
                ]));
            }

            $this->operation = $operation->model;
        }
    }
}
