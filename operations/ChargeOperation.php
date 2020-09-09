<?php

namespace app\billing\operations;

class ChargeOperation extends BaseOperation
{
    /**
     * Сумма для пополнения
     * @var int
     */
    public int $amount;

    /**
     *
     * @var int
     */
    public int $orderId;

    public function getDelta()
    {
        return abs($this->amount);
    }

    public function getTitle()
    {
        return \Yii::t('app', 'Пополнение счета');
    }

    public function getRefId()
    {
        return $this->orderId;
    }
}
