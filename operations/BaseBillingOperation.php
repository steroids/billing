<?php

namespace steroids\billing\operations;

use steroids\billing\BillingExecuteEvent;
use steroids\billing\BillingModule;
use steroids\billing\exceptions\BillingException;
use steroids\billing\exceptions\InsufficientFundsException;
use steroids\billing\models\BillingOperation;
use steroids\billing\models\BillingAccount;
use steroids\core\base\Model;
use steroids\core\exceptions\ModelSaveException;
use yii\base\BaseObject;
use yii\base\Exception;
use yii\base\InvalidConfigException;
use yii\helpers\ArrayHelper;

/**
 * @property string $name
 * @property BillingOperation $model
 * @property BillingAccount $fromAccount
 * @property BillingAccount $toAccount
 * @property BillingAccount $document
 */
class BaseBillingOperation extends BaseOperation
{
    public ?int $amount = null;

    /**
     * @var int
     */
    public int $fromAccountId;

    /**
     * @var int
     */
    public int $toAccountId;

    /**
     * @var BillingOperation|null
     */
    private ?BillingOperation $_model = null;

    /**
     * @var BillingAccount|bool
     */
    private $_fromAccount = false;

    /**
     * @var BillingAccount|bool
     */
    private $_toAccount = false;

    /**
     * @return int
     */
    public function getDelta()
    {
        return $this->amount;
    }

    /**
     * @return string|null
     */
    public function getTitle()
    {
        return null;
    }

    /**
     * @return BillingOperation|null
     */
    public function getModel()
    {
        return $this->_model;
    }

    /**
     * @param BillingOperation $value
     * @return void
     */
    public function setModel(BillingOperation $value)
    {
        $this->_model = $value;
    }

    /**
     * @return bool|BillingAccount|null
     * @throws \yii\base\Exception
     */
    public function getFromAccount()
    {
        if ($this->_fromAccount === false) {
            /** @var BillingAccount $billingAccountClass */
            $billingAccountClass = BillingModule::resolveClass(BillingAccount::class);
            $this->_fromAccount = $billingAccountClass::findOne(['id' => $this->fromAccountId]);
        }
        return $this->_fromAccount;
    }

    /**
     * @param BillingAccount $value
     */
    public function setFromAccount(BillingAccount $value)
    {
        $this->_fromAccount = $value;
        $this->fromAccountId = $value->primaryKey;
    }

    /**
     * @return bool|BillingAccount|null
     * @throws \yii\base\Exception
     */
    public function getToAccount()
    {
        if ($this->_toAccount === false) {
            /** @var BillingAccount $billingAccountClass */
            $billingAccountClass = BillingModule::resolveClass(BillingAccount::class);
            $this->_toAccount = $billingAccountClass::findOne(['id' => $this->toAccountId]);
        }
        return $this->_toAccount;
    }

    /**
     * @param BillingAccount $value
     */
    public function setToAccount(BillingAccount $value)
    {
        $this->_toAccount = $value;
        $this->toAccountId = $value->primaryKey;
    }

    /**
     * @param array $data
     * @return BaseOperation
     * @throws BillingException
     * @throws \yii\base\Exception
     */
    public static function createFromArray(array $data)
    {
        if (empty($data['fromAccountId']) || empty($data['toAccountId'])) {
            throw new BillingException('Params "fromAccountId" and "toAccountId" is required for create operation.');
        }

        return parent::createFromArray($data);
    }

    /**
     * @return array
     * @throws \ReflectionException
     */
    public function attributes()
    {
        $class = new \ReflectionClass($this);
        $names = [];
        foreach ($class->getProperties(\ReflectionProperty::IS_PUBLIC) as $property) {
            if (!$property->isStatic()) {
                $names[] = $property->getName();
            }
        }

        return [
            ...parent::attributes(),
            'fromAccountId',
            'toAccountId',
            'amount',
        ];
    }

    public function executeOnce()
    {
        $condition = [
            'name' => $this->name,
            'toAccountId' => $this->toAccountId,
            'fromAccountId' => $this->fromAccountId,
            'documentId' => $this->documentId,
        ];
        if (!BillingOperation::find()->where($condition)->exists()) {
            $this->execute();
        }
    }

    /**
     * @throws BillingException
     * @throws InsufficientFundsException
     * @throws ModelSaveException
     * @throws Exception
     */
    public function execute()
    {
        // Check already executed
        if ($this->_model) {
            throw new BillingException('Operation `' . static::class . '` already executed!');
        }

        // Check currency
        if ($this->fromAccount->currencyId !== $this->toAccount->currencyId) {
            throw new BillingException("Accounts have different currencies: {$this->fromAccount->currencyId}, {$this->toAccount->currencyId}");
        }

        $delta = $this->getDelta();
        if (!is_int($delta) || $delta <= 0) {
            throw new BillingException('Delta incorrect or cannot be zero! Value: ' . $delta);
        }

        // Decrease in deposit account, if main is insufficiently balance
        if (\Yii::$app->db->getTransaction()) {
            $this->executeInTransaction($delta);
        } else {
            $transaction = \Yii::$app->db->beginTransaction();
            try {
                $this->executeInTransaction($delta);
                $transaction->commit();
            } catch (\Exception $e) {
                $transaction->rollBack();
                throw $e;
            }
        }

        // Reset account, because balance updated
        $this->_fromAccount = null;
        $this->_toAccount = null;
    }

    /**
     * @return bool
     */
    public final function validateBalances()
    {
        $delta = $this->getDelta();
        return ($this->fromAccount->mayBeNegative() || $this->fromAccount->balance - $delta > 0)
            && ($this->toAccount->mayBeNegative() || $this->fromAccount->balance + $delta > 0);
    }

    /**
     * @throws Exception
     */
    public function afterExecute()
    {
        // Trigger event
        BillingModule::getInstance()->trigger(BillingModule::EVENT_OPERATION_EXECUTE, new BillingExecuteEvent([
            'sender' => $this,
        ]));
    }

    /**
     * @param $delta
     * @throws InsufficientFundsException
     * @throws ModelSaveException
     * @throws Exception
     */
    private function executeInTransaction($delta)
    {
        // Create model instance
        $this->_model = BillingOperation::instantiate([
            'name' => $this->name,
            'currencyId' => $this->fromAccount->currencyId,
            'fromAccountId' => $this->fromAccount->primaryKey,
            'toAccountId' => $this->toAccount->primaryKey,
            'delta' => $delta,
            'documentId' => $this->documentId,
        ]);

        // Save operation
        $this->_model->saveOrPanic();

        // Set relations
        $this->_model->populateRelation('fromAccount', $this->fromAccount);
        $this->_model->populateRelation('toAccount', $this->toAccount);

        // Update balances
        $this->fromAccount->updateBalance(-1 * $delta);
        $this->toAccount->updateBalance($delta);

        // Run custom action
        $this->afterExecute();
        $this->saveDocument();

        if ($this->documentId !== $this->_model->documentId) {
            $this->_model->updateAttributes([
                'documentId' => $this->documentId,
            ]);
        }
    }

}
