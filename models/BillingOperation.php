<?php

namespace steroids\billing\models;

use steroids\billing\BillingModule;
use steroids\billing\models\meta\BillingOperationMeta;
use steroids\billing\operations\BaseOperation;

/**
 * Class BillingOperation
 * @package steroids\billing\models
 * @property-read string $title
 * @property-read BaseOperation $operation
 * @property-read BillingCurrency $currency
 */
class BillingOperation extends BillingOperationMeta
{
    private ?BaseOperation $_operation = null;

    /**
     * @inheritDoc
     */
    public static function instantiate($row)
    {
        return BillingModule::instantiateClass(static::class, $row);
    }

    /**
     * @return string|null
     */
    public function getTitle()
    {
        return $this->operation ? $this->operation->getTitle() : null;
    }

    /**
     * @return BaseOperation
     */
    public function getOperation()
    {
        if (!$this->_operation) {
            /** @var BaseOperation $className */
            $className = BillingModule::getInstance()->getOperationClass($this->name);
            $params = [
                'name' => $this->name,
                'fromAccountId' => $this->fromAccountId,
                'toAccountId' => $this->toAccountId,
                'documentId' => $this->documentId,
                'model' => $this,
            ];
            if ($this->isRelationPopulated('fromAccount')) {
                $params['fromAccount'] = $this->fromAccount;
            }
            if ($this->isRelationPopulated('toAccount')) {
                $params['toAccount'] = $this->toAccount;
            }

            $this->_operation = new $className($params);
        }

        return $this->_operation;
    }

    /**
     * @return BillingCurrency
     * @throws \steroids\billing\exceptions\BillingException
     */
    public function getCurrency()
    {
        return BillingCurrency::getById($this->currencyId);
    }
}
