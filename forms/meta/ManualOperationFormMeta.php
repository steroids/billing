<?php

namespace steroids\billing\forms\meta;

use steroids\core\base\FormModel;
use \Yii;

abstract class ManualOperationFormMeta extends FormModel
{
    public ?string $currencyCode = null;
    public ?string $fromAccountName = null;
    public ?int $toUserId = null;
    public ?string $toAccountName = null;
    public ?float $amount = null;
    public ?string $comment = null;


    public function rules()
    {
        return [
            ...parent::rules(),
            [['currencyCode', 'fromAccountName', 'toAccountName'], 'string', 'max' => 255],
            [['currencyCode', 'fromAccountName', 'toUserId', 'toAccountName', 'amount'], 'required'],
            ['toUserId', 'integer'],
            ['amount', 'number'],
            ['comment', 'string'],
        ];
    }

    public static function meta()
    {
        return [
            'currencyCode' => [
                'label' => Yii::t('steroids', 'Валюта'),
                'isRequired' => true,
                'isSortable' => false
            ],
            'fromAccountName' => [
                'label' => Yii::t('steroids', 'Системный аккаунт'),
                'isRequired' => true
            ],
            'toUserId' => [
                'label' => Yii::t('steroids', 'Пользователь'),
                'appType' => 'integer',
                'isRequired' => true
            ],
            'toAccountName' => [
                'label' => Yii::t('steroids', 'Аккаунт'),
                'isRequired' => true
            ],
            'amount' => [
                'label' => Yii::t('steroids', 'Сумма'),
                'appType' => 'double',
                'isRequired' => true,
                'scale' => '2'
            ],
            'comment' => [
                'label' => Yii::t('steroids', 'Комментарий'),
                'appType' => 'text',
                'isSortable' => false
            ]
        ];
    }
}
