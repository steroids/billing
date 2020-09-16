<?php

namespace steroids\billing\forms;

use app\auth\AuthModule;
use steroids\auth\UserInterface;
use steroids\billing\forms\meta\OperationsSearchMeta;
use steroids\core\base\Model;

class OperationsSearch extends OperationsSearchMeta
{
    /**
     * @var UserInterface|Model
     */
    public $user;

    public function fields()
    {
        return [
            'id',
            'name',
            'title',
            'currencyId',
            'fromAccount' => [
                'id',
                'name',
                'user',
            ],
            'toAccount' => [
                'id',
                'name',
                'user',
            ],
            'documentId',
            'delta',
            'createTime',
        ];
    }

    public function prepare($query)
    {
        $query
            ->joinWith('fromAccount.user')
            ->joinWith('toAccount.user')
            ->andFilterWhere([
                'name' => $this->operationName,
                'currencyId' => $this->currencyId,
                'fromAccount.name' => $this->fromAccountName,
                'toAccount.name' => $this->toAccountName,
                'documentId' => $this->documentId,
            ])
            ->addOrderBy(['id' => SORT_DESC]);

        // Users search
        if ($this->fromUserQuery || $this->toUserQuery) {
            foreach (AuthModule::getInstance()->loginAvailableAttributes as $attributeType) {
                $attribute = AuthModule::getInstance()->getUserAttributeName($attributeType);
                $query->andFilterWhere(['like', 'fromAccount.user.' . $attribute, $this->fromUserQuery]);
                $query->andFilterWhere(['like', 'toAccount.user.' . $attribute, $this->toUserQuery]);
            }
        }

        // Context user query
        if ($this->user) {
            $query->andWhere([
                'or',
                ['fromAccount.user.id' => $this->user->primaryKey],
                ['toAccount.user.id' => $this->user->primaryKey],
            ]);
        }
    }
}
