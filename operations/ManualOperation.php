<?php

namespace app\billing\operations;

class ManualOperation extends BaseOperation
{
    public int $amount;

    public string $comment;

    public function getDelta()
    {
        return (float)$this->amount;
    }

    public function getTitle()
    {
        return $this->comment;
    }
}