<?php

namespace steroids\billing\forms\meta;

use steroids\billing\enums\ManualOperationEnum;
use steroids\core\base\FormModel;
use \Yii;

abstract class ManualOperationFormMeta extends FormModel
{
    public $operationName;
    public $currencyCode;
    public $amount;
    public $accountId;
    public $comment;

    public function rules()
    {
        return [
            ['operationName', 'in', 'range' => ManualOperationEnum::getKeys()],
            [['operationName', 'currencyCode', 'amount', 'accountId'], 'required'],
            ['currencyCode', 'string', 'max' => 255],
            ['amount', 'number'],
            ['accountId', 'integer'],
            ['comment', 'string'],
        ];
    }

    public static function meta()
    {
        return [
            'operationName' => [
                'label' => Yii::t('app', 'Операция'),
                'appType' => 'enum',
                'isRequired' => true,
                'enumClassName' => ManualOperationEnum::class
            ],
            'currencyCode' => [
                'label' => Yii::t('app', 'Валюта'),
                'isRequired' => true
            ],
            'amount' => [
                'label' => Yii::t('app', 'Количество'),
                'appType' => 'double',
                'isRequired' => true
            ],
            'accountId' => [
                'label' => Yii::t('app', 'Аккаунт'),
                'appType' => 'integer',
                'isRequired' => true
            ],
            'comment' => [
                'label' => Yii::t('app', 'Комментарий'),
                'appType' => 'text'
            ]
        ];
    }
}
