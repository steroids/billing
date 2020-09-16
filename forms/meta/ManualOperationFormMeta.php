<?php

namespace steroids\billing\forms\meta;

use steroids\core\base\FormModel;
use \Yii;

abstract class ManualOperationFormMeta extends FormModel
{
    public ?int $fromAccountId = null;
    public ?int $toUserId = null;
    public ?string $toAccountName = null;
    public ?float $amount = null;
    public ?string $comment = null;

    public function rules()
    {
        return [
            ...parent::rules(),
            [['fromAccountId', 'toUserId', 'toAccountName', 'amount'], 'required'],
            [['fromAccountId', 'toUserId'], 'integer'],
            ['amount', 'number'],
            [['toAccountName', 'comment'], 'string'],
        ];
    }

    public static function meta()
    {
        return [
            'fromAccountId' => [
                'label' => Yii::t('steroids', 'Системный аккаунт'),
                'appType' => 'integer',
                'isRequired' => true
            ],
            'toUserId' => [
                'label' => Yii::t('steroids', 'Пользователь'),
                'appType' => 'integer',
                'isRequired' => true
            ],
            'toAccountName' => [
                'label' => Yii::t('steroids', 'Аккаунт'),
                'appType' => 'string',
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
