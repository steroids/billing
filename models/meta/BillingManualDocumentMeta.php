<?php

namespace steroids\billing\models\meta;

use steroids\core\base\Model;

/**
 * @property string $id
 * @property integer $userId
 * @property string $ipAddress
 * @property string $comment
 */
abstract class BillingManualDocumentMeta extends Model
{
    public static function tableName()
    {
        return 'billing_manual_documents';
    }

    public function fields()
    {
        return [
        ];
    }

    public function rules()
    {
        return [
            ...parent::rules(),
            ['userId', 'integer'],
            ['ipAddress', 'string', 'max' => '64'],
            ['comment', 'string'],
        ];
    }

    public static function meta()
    {
        return array_merge(parent::meta(), [
            'id' => [
                'appType' => 'primaryKey',
                'isPublishToFrontend' => false
            ],
            'userId' => [
                'appType' => 'integer',
                'isPublishToFrontend' => false
            ],
            'ipAddress' => [
                'isPublishToFrontend' => false,
                'stringLength' => '64'
            ],
            'comment' => [
                'appType' => 'text',
                'isPublishToFrontend' => false
            ]
        ]);
    }
}
