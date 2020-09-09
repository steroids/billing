<?php

namespace steroids\billing\forms;

use steroids\account\models\Account;
use steroids\billing\enums\ManualOperationEnum;
use steroids\billing\exceptions\InsufficientFundsException;
use steroids\billing\forms\meta\ManualOperationFormMeta;
use steroids\billing\models\BillingWallet;

/**
 * Class ManualOperationForm
 * @property-read BillingWallet $wallet
 */
class ManualOperationForm extends ManualOperationFormMeta
{
    public function rules()
    {
        return array_merge(parent::rules(), [
            ['comment', 'required', 'when' => function () {
                return $this->operationName === ManualOperationEnum::ANY;
            }],
        ]);
    }

    /**
     * @return bool
     * @throws \app\billing\exceptions\BillingException
     */
    public function charge()
    {
        if ($this->validate()) {
            $operation = $this->wallet->createOperation(ManualOperationEnum::getOperationClass($this->operationName), [
                'amount' => $this->amount,
                'comment' => $this->comment,
            ]);

            try {
                $operation->execute();
                return true;
            } catch (InsufficientFundsException $e) {
                $this->addError('amount', \Yii::t('app', 'На балансе недостаточно средств: {amount}', [
                    'amount' => $e->balance,
                ]));
            }

        }
        return false;
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getAccount()
    {
        return $this->hasOne(Account::class, ['id' => 'accountId']);
    }

    /**
     * @return \app\billing\models\BillingWallet
     * @throws \steroids\exceptions\ModelSaveException
     */
    public function getWallet()
    {
        return $this->account->getWallet($this->currencyCode);

    }
}
