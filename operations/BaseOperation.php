<?php

namespace steroids\billing\operations;

use steroids\billing\BillingExecuteEvent;
use steroids\billing\BillingModule;
use steroids\billing\exceptions\BillingException;
use steroids\billing\exceptions\InsufficientFundsException;
use steroids\billing\models\BillingOperation;
use steroids\billing\models\BillingAccount;
use steroids\core\base\Model;
use yii\base\BaseObject;
use yii\base\Event;
use yii\base\InvalidConfigException;

/**
 * @property string $operationName
 * @property BillingOperation $model
 * @property BillingAccount $fromAccount
 * @property BillingAccount $toAccount
 * @property BillingAccount $document
 */
abstract class BaseOperation extends BaseObject
{
    public string $name;

    /**
     * @var int
     */
    public int $fromAccountId;

    /**
     * @var int
     */
    public int $toAccountId;

    /**
     * @var int
     */
    public ?int $documentId = null;

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
     * @var Model|bool
     */
    private $_document = false;

    public static function getDocumentClass()
    {
        return null;
    }

    abstract public function getDelta();

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
    }

    public function getDocument()
    {
        if ($this->_document === false) {
            $this->_document = null;

            /** @var Model $documentClass */
            $documentClass = static::getDocumentClass();
            if ($documentClass && $this->documentId) {
                $pk = $documentClass::primaryKey()[0];
                $this->_document = $documentClass::findOne([$pk => $this->documentId]);
            }
        }
        return $this->_document;
    }

    /**
     * @param array|Model $value
     */
    public function setDocument($value)
    {
        if (is_array($value)) {
            $documentClass = static::getDocumentClass();
            $value = new $documentClass($value);
        }
        $this->_document = $value;

        if ($this->_document->primaryKey) {
            $this->documentId = $this->_document->primaryKey;
        }
    }

    /**
     * @throws BillingException
     * @throws InsufficientFundsException
     * @throws InvalidConfigException
     * @throws \steroids\core\exceptions\ModelSaveException
     * @throws \yii\base\Exception
     */
    public final function execute()
    {
        // Check already executed
        if ($this->_model) {
            throw new BillingException('Operation `' . static::class . '` already executed!');
        }

        // Check currency
        if ($this->fromAccount->currencyId !== $this->toAccount->currencyId) {
            throw new BillingException("Accounts have different currencies: {$this->fromAccount->currencyId}, {$this->toAccount->currencyId}");
        }

        $delta = (int)$this->getDelta();
        if ($delta === 0) {
            throw new BillingException('Delta incorrect or cannot be zero!');
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
     * @throws \yii\base\Exception
     */
    public function afterExecute()
    {
        $this->saveDocument();

        // Trigger event
        BillingModule::getInstance()->trigger(BillingModule::EVENT_OPERATION_EXECUTE, new BillingExecuteEvent([
            'sender' => $this,
        ]));
    }

    /**
     * @throws \steroids\core\exceptions\ModelSaveException
     */
    protected function saveDocument()
    {
        /** @var Model $documentClass */
        $documentClass = static::getDocumentClass();
        if (!$documentClass) {
            return;
        }

        // Save document
        if ($this->_document instanceof Model) {
            $this->_document->saveOrPanic();
            $this->documentId = $this->_document->primaryKey;
        }
    }


    /**
     * @param $delta
     * @throws InsufficientFundsException
     * @throws \steroids\core\exceptions\ModelSaveException
     * @throws \yii\base\Exception
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
        if ($this->documentId !== $this->_model->documentId) {
            $this->_model->updateAttributes([
                'documentId' => $this->documentId,
            ]);
        }
    }

}
