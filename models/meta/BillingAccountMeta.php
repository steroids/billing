<?php

namespace steroids\billing\models\meta;

use steroids\core\base\Model;
use \Yii;
use yii\db\ActiveQuery;
use steroids\billing\models\BillingCurrency;

/**
 * @property string $id
 * @property string $name
 * @property integer $userId
 * @property integer $currencyId
 * @property integer $balance
 */
abstract class BillingAccountMeta extends Model
{
    public static function tableName()
    {
        return 'billing_accounts';
    }

    public function fields()
    {
        return [
            'id',
            'name',
            'userId',
            'currencyId',
            'balance',
        ];
    }

    public function rules()
    {
        return [
            ...parent::rules(),
            ['name', 'string', 'max' => '32'],
            ['name', 'required'],
            [['userId', 'currencyId', 'balance'], 'integer'],
        ];
    }

    public static function meta()
    {
        return array_merge(parent::meta(), [
            'id' => [
                'label' => Yii::t('steroids', 'ID'),
                'example' => '1',
                'appType' => 'primaryKey',
                'isPublishToFrontend' => true
            ],
            'name' => [
                'label' => Yii::t('steroids', 'Название'),
                'example' => 'main',
                'isRequired' => true,
                'isPublishToFrontend' => true,
                'stringLength' => '32'
            ],
            'userId' => [
                'label' => Yii::t('steroids', 'Пользователь'),
                'example' => '52',
                'appType' => 'integer',
                'isPublishToFrontend' => true
            ],
            'currencyId' => [
                'label' => Yii::t('steroids', 'Валюта'),
                'example' => '1',
                'appType' => 'integer',
                'isPublishToFrontend' => true
            ],
            'balance' => [
                'label' => Yii::t('steroids', 'Баланс'),
                'example' => '5000',
                'appType' => 'integer',
                'isPublishToFrontend' => true
            ]
        ]);
    }
}
