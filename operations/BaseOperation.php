<?php

namespace steroids\billing\operations;

use steroids\billing\BillingModule;
use steroids\billing\exceptions\BillingException;
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
class BaseOperation extends BaseObject
{
    /**
     * @var int
     */
    public ?int $documentId = null;

    /**
     * @var Model|bool
     */
    private $_document = false;

    /**
     * @return string|BaseObject|null
     */
    public static function getDocumentClass()
    {
        return null;
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

        $name = ArrayHelper::remove($data, 'name');
        if (!$name) {
            throw new BillingException('Param "name" is required for create operation.');
        }

        /** @var BaseOperation $operationClass */
        $operationClass = BillingModule::getInstance()->getOperationClass($name);
        return new $operationClass($data);
    }

    /**
     * @return string
     * @throws InvalidConfigException
     * @throws \yii\base\Exception
     */
    public function getName()
    {
        return BillingModule::getInstance()->getOperationName(get_class($this));
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
     * @return array
     * @throws \ReflectionException
     */
    public function attributes()
    {
        $class = new \ReflectionClass($this);
        $names = [
            'name',
            'documentId',
        ];
        foreach ($class->getProperties(\ReflectionProperty::IS_PUBLIC) as $property) {
            if (!$property->isStatic()) {
                $names[] = $property->getName();
            }
        }

        return $names;
    }

    /**
     * @return array
     */
    public function toArray()
    {
        $result = [];
        foreach ($this->attributes() as $attribute) {
            $result[$attribute] = $this->$attribute;
        }
        return $result;
    }

    /**
     * @throws ModelSaveException
     * @throws Exception
     */
    public function execute()
    {
        $this->afterExecute();
        $this->saveDocument();
    }

    /**
     *
     */
    public function afterExecute()
    {
    }

    /**
     * @throws ModelSaveException
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
}
