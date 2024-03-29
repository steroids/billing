<?php

namespace steroids\billing\models;

use steroids\auth\AuthModule;
use steroids\auth\UserInterface;
use steroids\billing\BillingModule;
use steroids\billing\exceptions\InsufficientFundsException;
use steroids\billing\exceptions\BillingException;
use steroids\billing\models\meta\BillingAccountMeta;
use steroids\core\base\Model;
use yii\db\ActiveQuery;

/**
 * Class BillingAccount
 * @package steroids\billing\models
 * @property-read UserInterface|Model $user
 * @property-read bool $isSystem
 * @property-read bool $isUser
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
     * @param string $currencyCode
     * @return BillingAccount
     * @throws \steroids\billing\exceptions\BillingException
     * @throws \yii\web\NotFoundHttpException
     */
    public static function findSystem(string $name, string $currencyCode)
    {
        $currency = BillingCurrency::getByCode($currencyCode);
        $account = static::findOrPanic([
            'name' => $name,
            'currencyId' => $currency->primaryKey,
            'userId' => null,
        ]);
        $account->populateRelation('currency', $currency);
        return $account;
    }

    /**
     * Find or create users account. For system account use findSystem()
     * @param string $name
     * @param string $currencyCode
     * @param int $userId
     * @return BillingAccount
     * @throws \steroids\core\exceptions\ModelSaveException
     */
    public static function findOrCreate(string $name, string $currencyCode, int $userId)
    {
        $currency = BillingCurrency::getByCode($currencyCode);
        $params = [
            'currencyId' => $currency->primaryKey,
            'name' => $name,
            'userId' => $userId,
        ];
        $account = static::find()->where($params)->limit(1)->one();
        if (!$account) {
            $account = new static($params);
            $account->saveOrPanic();
        }
        $account->populateRelation('currency', $currency);
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
     * @param string $operationClassName
     * @param array $params
     * @return mixed
     */
    public function createOperation(BillingAccount $toAccount, string $operationClassName, array $params = [])
    {
        return new $operationClassName(array_merge($params, [
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
        if ($delta === 0) {
            return;
        } elseif ($delta < 0) {
            $this->decreaseBalance($delta);
        } else {
            $this->increaseBalance($delta);
        }
    }

    /**
     * @param int $delta
     * @throws BillingException
     * @throws InsufficientFundsException
     */
    protected function decreaseBalance($delta)
    {
        if ($delta > 0) {
            throw new BillingException('Wrong delta value');
        }

        $condition = array_filter([
            'and',
            ['id' => $this->primaryKey],
            $this->nonNegativeCondition($delta)
        ]);

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
     * @param int $delta
     * @return array|null
     */
    protected function nonNegativeCondition($delta)
    {
        return !$this->mayBeNegative()
            ? ['>=', 'balance', abs($delta)]
            : null;
    }

    protected function increaseBalance($delta)
    {
        if ($delta < 0) {
            throw new BillingException('Wrong delta value');
        }

        $condition = array_filter([
            'and',
            ['id' => $this->primaryKey],
            $this->topLimitCondition($delta)
        ]);

        $result = static::updateAllCounters(['balance' => $delta], $condition);
        if ($result !== 1) {
            throw new BillingException("Account has top limit for increase balance: currency {$this->currencyId}, {$this->balance} + {$delta}");
        }

        $this->balance += $delta;
    }

    /**
     * @param int $delta
     * @return array|null
     */
    protected function topLimitCondition($delta)
    {
        return null;
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

    /**
     * @return bool
     * @throws \yii\base\Exception
     * @throws \yii\base\InvalidConfigException
     */
    public function getIsSystem()
    {
        return in_array($this->name, BillingModule::getInstance()->getSystemAccountNames());
    }

    /**
     * @return bool
     * @throws \yii\base\Exception
     * @throws \yii\base\InvalidConfigException
     */
    public function getIsUser()
    {
        return in_array($this->name, BillingModule::getInstance()->getUserAccountNames());
    }
}
