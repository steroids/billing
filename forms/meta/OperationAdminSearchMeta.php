<?php

namespace steroids\billing\forms\meta;

use steroids\core\base\SearchModel;
use \Yii;
use steroids\billing\models\BillingOperation;

abstract class OperationAdminSearchMeta extends SearchModel
{
    public $id;
    public $email;

    public function rules()
    {
        return [
            ['id', 'integer'],
            ['email', 'string', 'max' => 255],
        ];
    }

    public function sortFields()
    {
        return [

        ];
    }

    public function createQuery()
    {
        return BillingOperation::find();
    }

    public static function meta()
    {
        return [
            'id' => [
                'label' => Yii::t('app', 'ID'),
                'appType' => 'integer',
                'isSortable' => false
            ],
            'email' => [
                'label' => Yii::t('app', 'Email'),
                'isSortable' => false
            ]
        ];
    }
}
