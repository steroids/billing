<?php

namespace steroids\billing\models\meta;

use steroids\core\base\Model;
use steroids\core\behaviors\TimestampBehavior;
use \Yii;

/**
 * @property string $id
 * @property string $code
 * @property integer $precision
 * @property string $label
 * @property integer $rateUsd
 * @property integer $ratePrecision
 * @property boolean $isVisible
 * @property string $createTime
 * @property string $updateTime
 */
abstract class BillingCurrencyMeta extends Model
{
    public static function tableName()
    {
        return 'billing_currencies';
    }

    public function fields()
    {
        return [
        ];
    }

    public function rules()
    {
        return array_merge(parent::rules(), [
            ['code', 'string', 'max' => '32'],
            [['code', 'precision'], 'required'],
            [['precision', 'rateUsd', 'ratePrecision'], 'integer'],
            ['label', 'string', 'max' => 255],
            ['isVisible', 'steroids\\core\\validators\\ExtBooleanValidator'],
        ]);
    }

    public function behaviors()
    {
        return array_merge(parent::behaviors(), [
            TimestampBehavior::class,
        ]);
    }

    public static function meta()
    {
        return array_merge(parent::meta(), [
            'id' => [
                'label' => Yii::t('steroids', 'ID'),
                'example' => '1',
                'appType' => 'primaryKey',
                'isPublishToFrontend' => false
            ],
            'code' => [
                'label' => Yii::t('steroids', 'Код'),
                'example' => 'usd',
                'isRequired' => true,
                'isPublishToFrontend' => false,
                'stringLength' => '32'
            ],
            'precision' => [
                'label' => Yii::t('steroids', 'Точность'),
                'hint' => Yii::t('steroids', 'Количество знаков после запятой'),
                'example' => '2',
                'appType' => 'integer',
                'isRequired' => true,
                'isPublishToFrontend' => false
            ],
            'label' => [
                'label' => Yii::t('steroids', 'Название'),
                'example' => 'Доллар',
                'isPublishToFrontend' => false
            ],
            'rateUsd' => [
                'label' => Yii::t('steroids', 'Курс к доллару'),
                'example' => '7051',
                'appType' => 'integer',
                'isPublishToFrontend' => false
            ],
            'ratePrecision' => [
                'label' => Yii::t('steroids', 'Точность курса'),
                'hint' => Yii::t('steroids', 'Количество знаков после запятой'),
                'example' => '2',
                'appType' => 'integer',
                'isPublishToFrontend' => false
            ],
            'isVisible' => [
                'label' => Yii::t('steroids', 'Отображать на сайте?'),
                'example' => true,
                'appType' => 'boolean',
                'isPublishToFrontend' => false
            ],
            'createTime' => [
                'label' => Yii::t('steroids', 'Дата создания'),
                'appType' => 'autoTime',
                'isPublishToFrontend' => false,
                'touchOnUpdate' => false
            ],
            'updateTime' => [
                'label' => Yii::t('steroids', 'Дата обновления'),
                'appType' => 'autoTime',
                'isPublishToFrontend' => false,
                'touchOnUpdate' => true
            ]
        ]);
    }
}
