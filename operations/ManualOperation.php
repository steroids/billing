<?php

namespace steroids\billing\operations;

use steroids\billing\models\BillingManualDocument;

/**
 * Class ManualOperation
 * @property-read BillingManualDocument $document
 */
class ManualOperation extends BaseOperation
{
    public int $amount;

    public function getDelta()
    {
        return (int)$this->amount;
    }

    public function getTitle()
    {
        return $this->document->comment;
    }

    public static function getDocumentClass()
    {
        return BillingManualDocument::class;
    }
}