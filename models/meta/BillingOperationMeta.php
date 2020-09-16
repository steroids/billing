<?php

namespace steroids\billing\models\meta;

use steroids\core\base\Model;
use steroids\core\behaviors\TimestampBehavior;
use \Yii;
use yii\db\ActiveQuery;
use steroids\billing\models\BillingAccount;
use steroids\billing\models\BillingCurrency;

/**
 * @property string $id
 * @property string $name
 * @property integer $currencyId
 * @property integer $fromAccountId
 * @property integer $toAccountId
 * @property integer $documentId
 * @property integer $delta
 * @property string $createTime
 * @property-read BillingAccount $fromAccount
 * @property-read BillingAccount $toAccount
 * @property-read BillingCurrency $currency
 */
abstract class BillingOperationMeta extends Model
{
    public static function tableName()
    {
        return 'billing_operations';
    }

    public function fields()
    {
        return [
            'id',
            'name',
            'currencyId',
            'fromAccountId',
            'toAccountId',
            'delta',
            'createTime',
        ];
    }

    public function rules()
    {
        return [
            ...parent::rules(),
            ['name', 'string', 'max' => 255],
            [['name', 'currencyId', 'fromAccountId', 'toAccountId'], 'required'],
            [['currencyId', 'fromAccountId', 'toAccountId', 'documentId', 'delta'], 'integer'],
        ];
    }

    public function behaviors()
    {
        return [
            ...parent::behaviors(),
            TimestampBehavior::class,
        ];
    }

    /**
     * @return ActiveQuery
     */
    public function getFromAccount()
    {
        return $this->hasOne(BillingAccount::class, ['id' => 'fromAccountId']);
    }

    /**
     * @return ActiveQuery
     */
    public function getToAccount()
    {
        return $this->hasOne(BillingAccount::class, ['id' => 'toAccountId']);
    }

    /**
     * @return ActiveQuery
     */
    public function getCurrency()
    {
        return $this->hasOne(BillingCurrency::class, ['id' => 'currencyId']);
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
                'label' => Yii::t('steroids', 'Название операции'),
                'example' => 'main',
                'isRequired' => true,
                'isPublishToFrontend' => true
            ],
            'currencyId' => [
                'label' => Yii::t('steroids', 'Валюта'),
                'appType' => 'integer',
                'isRequired' => true,
                'isPublishToFrontend' => true
            ],
            'fromAccountId' => [
                'label' => Yii::t('steroids', 'Источник'),
                'example' => '52',
                'appType' => 'integer',
                'isRequired' => true,
                'isPublishToFrontend' => true
            ],
            'toAccountId' => [
                'label' => Yii::t('steroids', 'Получатель'),
                'appType' => 'integer',
                'isRequired' => true,
                'isPublishToFrontend' => true
            ],
            'documentId' => [
                'label' => Yii::t('steroids', 'Документ'),
                'example' => '33',
                'appType' => 'integer',
                'isPublishToFrontend' => false
            ],
            'delta' => [
                'label' => Yii::t('steroids', 'Сумма'),
                'example' => '-5000',
                'appType' => 'integer',
                'isPublishToFrontend' => true
            ],
            'createTime' => [
                'label' => Yii::t('steroids', 'Время операции'),
                'appType' => 'autoTime',
                'isPublishToFrontend' => true,
                'touchOnUpdate' => false
            ]
        ]);
    }
}
