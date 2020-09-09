<?php

namespace steroids\billing\forms;

use steroids\billing\forms\meta\OperationAdminSearchMeta;

class OperationAdminSearch extends OperationAdminSearchMeta
{
    public function prepare($query)
    {
        $query
            ->alias('operation')
            ->joinWith(['currency', 'account', 'account.creator'])

            ->andFilterWhere([
                'operation.id' => $this->id,
            ])
            ->andFilterWhere(['like', 'email', $this->email]);
    }

    public function createProvider()
    {
        return array_merge(parent::createProvider(), [
            'sort' => [
                'defaultOrder' => [
                    'id' => SORT_DESC,
                ],
            ],
        ]);
    }
}
