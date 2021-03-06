<?php

namespace steroids\billing\operations;

use steroids\billing\models\BillingManualDocument;

/**
 * Class ManualOperation
 * @property-read BillingManualDocument $document
 */
class ManualOperation extends BaseBillingOperation
{
    public function getTitle()
    {
        if ($this->document->comment) {
            return $this->document->comment;
        }
        return $this->getModel()->delta > 0
            ? \Yii::t('app', 'Ручное пополнение')
            : \Yii::t('app', 'Ручное списание');
    }

    public static function getDocumentClass()
    {
        return BillingManualDocument::class;
    }
}