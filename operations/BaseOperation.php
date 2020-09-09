<?php

namespace app\billing\operations;

use steroids\billing\BillingModule;
use steroids\billing\exceptions\BillingException;
use steroids\billing\exceptions\InsufficientFundsException;
use steroids\billing\models\BillingOperation;
use steroids\billing\models\BillingAccount;
use yii\base\BaseObject;
use yii\db\Expression;
use yii\helpers\Inflector;

/**
 * @property string $operationName
 * @property BillingOperation $model
 * @property BillingAccount $billingAccount
 */
abstract class BaseOperation extends BaseObject
{
    /**
     * @var string;
     */
    public string $name;

    /**
     * @var int
     */
    public int $accountId;

    /**
     * @var int
     */
    public int $currencyId;

    /**
     * @var BillingModule
     */
    public BillingModule $module;

    /**
     * @var string[]
     */
    public $constructorKeys = [];

    /**
     * @var BillingOperation|null
     */
    private ?BillingOperation $_model;

    /**
     * @var BillingAccount|bool
     */
    private $_account = false;

    /**
     * @param array [$config]
     * @param BillingOperation|null [$operationModel]
     * @throws BillingException
     */
    public function __construct($config = [], BillingOperation $operationModel = null)
    {
        // Check params types
        foreach ($config as $key => $value) {
            if (!is_null($value) && !is_bool($value) && !is_string($value) && !is_int($value) && !is_array($value) && !is_double($value) && !is_float($value) && !($value instanceof \StdClass)) {
                throw new BillingException('Only array, plain objects and scalar types available for create operation. Key `' . $key . '` has wrong type.');
            }
        }

        $this->constructorKeys = array_keys($config);
        $this->_model = $operationModel;

        parent::__construct($config);
    }

    abstract public function getDelta();

    public function getOperationName()
    {
        return $this->name ?: (new \ReflectionClass($this))->getShortName();
    }

    public function getTitle()
    {
        preg_match('/[^\\\]+$/', static::className(), $match);
        $title = Inflector::camel2id($match[0], '_');
        $title = Inflector::id2camel($title, '_');
        return Inflector::camel2words($title);
    }

    public function getDescription()
    {
        return '';
    }

    public function getRefId()
    {
        return null;
    }

    public function getModel()
    {
        return $this->_model;
    }

    public function getAccount()
    {
        if ($this->_account === false) {
            /** @var BillingAccount $billingAccountClass */
            $billingAccountClass = BillingModule::resolveClass(BillingAccount::class);
            $this->_account = $billingAccountClass::find()
                ->where([
                    'accountId' => $this->accountId,
                    'currencyId' => $this->currencyId,
                ])
                ->limit(1)
                ->one();
        }
        return $this->_account;
    }

    public function setAccount($value)
    {
        $this->_account = $value;
    }

    public final function execute()
    {
        // Check already executed
        if ($this->_model) {
            throw new BillingException('Operation `' . static::class . '` already executed!');
        }

        $delta = $this->getDelta();

        // Check balance
        if (!$this->haveFunds()) {
            $exception = new InsufficientFundsException('Insufficient funds' . '`, currencyId `' . $this->currencyId . '`, balance `' . $this->getAccount()->balance . '`, need `' . $delta . '`');
            $exception->balance = $this->getAccount()->balance;
            $exception->delta = $delta;
            throw $exception;
        }

        $this->_model = BillingOperation::instantiate([
            'accountId' => $this->accountId,
            'currencyId' => $this->currencyId,
            'delta' => $delta,
            'refId' => $this->getRefId(),
            'operationName' => $this->getOperationName(),
        ]);

        // Decrease in deposit account, if main is insufficiently balance
        if (\Yii::$app->db->getTransaction()) {
            $this->executeInTransaction();
        } else {
            $transaction = \Yii::$app->db->beginTransaction();
            try {
                $this->executeInTransaction();
                $transaction->commit();
            } catch (\Exception $e) {
                $transaction->rollBack();
                throw $e;
            }
        }

        // Reset account because balance updated
        $this->_account = null;
    }

    private function executeInTransaction()
    {
        // Save operation
        $this->_model->saveOrPanic();

        // Change summary balance
        $condition = [
            'accountId' => $this->accountId,
            'currencyId' => $this->currencyId,
        ];

        /** @var BillingAccount $billingAccountClass */
        $billingAccountClass = BillingModule::resolveClass(BillingAccount::class);

        $sql = $billingAccountClass::find()
            ->select(new Expression("(CASE WHEN balance + :delta >= 0 THEN TRUE ELSE FALSE END) AS allow", [
                ':delta' => $this->_model->delta,
            ]))
            ->where($condition)
            ->createCommand()->getRawSql();
        $allow = $billingAccountClass::findBySql($sql . ' FOR UPDATE')->scalar();
        if (!$allow) {
            $exception = new InsufficientFundsException('Insufficient funds on account (before update balance counter) `' . '`, currencyId `' . $this->currencyId . '`, balance `' . $this->getAccount()->balance . '`, need `' . $this->_model->delta . '`');
            $exception->balance = $this->getAccount()->balance;
            $exception->delta = $this->_model->delta;
            throw $exception;
        }
        $billingAccountClass::updateAllCounters([
            'balance' => $this->_model->delta,
        ], $condition);

        // Run custom action
        $this->executeInternal();
    }

    /**
     * @return bool
     */
    public final function haveFunds()
    {
        $delta = $this->getDelta();
        if ($delta >= 0) {
            return true;
        }

        return $this->getAccount()->balance >= abs($delta);
    }

    public function executeInternal()
    {
    }

}
