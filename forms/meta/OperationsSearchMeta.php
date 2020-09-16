<?php

namespace steroids\billing\forms\meta;

use steroids\core\base\SearchModel;
use \Yii;
use steroids\billing\models\BillingOperation;

abstract class OperationsSearchMeta extends SearchModel
{
    public ?string $operationName = null;
    public ?int $currencyId = null;
    public ?string $fromUserQuery = null;
    public ?string $toUserQuery = null;
    public ?string $fromAccountName = null;
    public ?string $toAccountName = null;
    public ?int $documentId = null;

    public function rules()
    {
        return [
            ...parent::rules(),
            [['operationName', 'fromUserQuery', 'toUserQuery', 'fromAccountName', 'toAccountName'], 'string', 'max' => 255],
            [['currencyId', 'documentId'], 'integer'],
        ];
    }

    public function sortFields()
    {
        return [];
    }

    public function createQuery()
    {
        return BillingOperation::find();
    }

    public static function meta()
    {
        return [
            'operationName' => [
                'label' => Yii::t('steroids', 'Имя операции'),
                'isSortable' => false
            ],
            'currencyId' => [
                'label' => Yii::t('steroids', 'Валюта'),
                'appType' => 'integer',
                'isSortable' => false
            ],
            'fromUserQuery' => [
                'label' => Yii::t('steroids', 'От пользователя (id/login)'),
                'isSortable' => false
            ],
            'toUserQuery' => [
                'label' => Yii::t('steroids', 'Пользователю (id/login)'),
                'isSortable' => false
            ],
            'fromAccountName' => [
                'label' => Yii::t('steroids', 'Имя исходящего аккаунта'),
                'isSortable' => false
            ],
            'toAccountName' => [
                'label' => Yii::t('steroids', 'Имя входящего аккаунта'),
                'isSortable' => false
            ],
            'documentId' => [
                'label' => Yii::t('steroids', 'ИД документа'),
                'appType' => 'integer',
                'isSortable' => false
            ]
        ];
    }
}
