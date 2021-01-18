<?php

namespace steroids\billing\operations;

use steroids\billing\models\BillingOperation;

/**
 * Class ManualOperation
 * @property-read BillingOperation $document
 */
class RollbackOperation extends BaseBillingOperation
{
    public function getTitle()
    {
        return \Yii::t('app', 'Отмена операции');
    }

    public static function getDocumentClass()
    {
        return BillingOperation::class;
    }
}