<?php

namespace steroids\billing\models;

use steroids\auth\AuthModule;
use steroids\auth\UserInterface;
use steroids\billing\BillingModule;
use steroids\billing\exceptions\InsufficientFundsException;
use steroids\billing\models\meta\BillingAccountMeta;
use steroids\billing\operations\BaseOperation;
use steroids\core\base\Model;
use yii\db\ActiveQuery;

/**
 * Class BillingAccount
 * @package steroids\billing\models
 * @property-read UserInterface|Model $user
 * @property-read BillingCurrency $currency
 */
class BillingAccount extends BillingAccountMeta
{
    /**
     * @inheritDoc
     */
    public static function instantiate($row)
    {
        return BillingModule::instantiateClass(static::class, $row);
    }

    /**
     * @param string $name
     * @param int $currencyId
     * @param int $userId
     * @return array|BillingAccount|\yii\db\ActiveRecord|static
     * @throws \steroids\core\exceptions\ModelSaveException
     */
    public static function findOrCreate(string $name, int $currencyId, int $userId = null)
    {
        $params = [
            'userId' => $userId,
            'currencyId' => $currencyId,
            'name' => $name,
        ];
        $account = static::find()->where($params)->limit(1)->one();
        if (!$account) {
            $account = new static($params);
            $account->saveOrPanic();
        }
        return $account;
    }

    /**
     * @inheritDoc
     */
    public function rules()
    {
        return [
            ...parent::rules(),
            ['balance', 'default', 'value' => 0],
        ];
    }

    /**
     * @param BillingAccount $toAccount
     * @param string|BaseOperation $operationClassName
     * @param array $params
     * @return BaseOperation
     * @throws \yii\base\Exception
     * @throws \yii\base\InvalidConfigException
     */
    public function createOperation(BillingAccount $toAccount, string $operationClassName, array $params = [])
    {
        return new $operationClassName(array_merge($params, [
            'name' => BillingModule::getInstance()->getOperationName($operationClassName),
            'fromAccount' => $this,
            'toAccount' => $toAccount,
        ]));
    }

    /**
     * @param int $delta
     * @throws InsufficientFundsException
     */
    public function updateBalance(int $delta)
    {
        $condition = ['id' => $this->primaryKey];
        if (!$this->mayBeNegative() && $delta < 0) {
            $condition = [
                'and',
                $condition,
                ['>=', 'balance', $delta]
            ];
        }

        $result = static::updateAllCounters(['balance' => $delta], $condition);
        if ($result !== 1) {
            $exception = new InsufficientFundsException("Insufficient funds on balance update: currency {$this->currencyId}, {$this->balance} + {$delta}");
            $exception->balance = $this->balance;
            $exception->delta = $delta;
            throw $exception;
        }

        $this->balance += $delta;
    }

    /**
     * @return bool
     */
    public function mayBeNegative()
    {
        // Only system accounts may be negative balance
        return !$this->userId;
    }

    /**
     * @return ActiveQuery
     */
    public function getUser()
    {
        return $this->hasOne(AuthModule::getInstance()->userClass, ['id' => 'userId']);
    }

    /**
     * @return BillingCurrency
     */
    public function getCurrency()
    {
        return BillingCurrency::getById($this->currencyId);
    }
}
