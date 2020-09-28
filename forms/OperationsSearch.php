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
            'title' => 'operation.title',
            'currency' => [
                'id',
                'code',
                'label',
                'precision',
            ],
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
            ->joinWith([
                'fromAccount fa',
                'fromAccount.user fau',
                'toAccount ta',
                'toAccount.user tau',
            ])
            ->andFilterWhere([
                'name' => $this->operationName,
                'currencyId' => $this->currencyId,
                'fromAccount.name' => $this->fromAccountName,
                'toAccount.name' => $this->toAccountName,
                'documentId' => $this->documentId,
            ])
            ->addOrderBy(['id' => SORT_DESC]);

        // Users search
        $likeConditions = [];
        if ($this->fromUserQuery || $this->toUserQuery) {
            foreach (AuthModule::getInstance()->loginAvailableAttributes as $attributeType) {
                $attribute = AuthModule::getInstance()->getUserAttributeName($attributeType);
                if ($this->fromUserQuery) {
                    $likeConditions[] = ['like', 'fau.' . $attribute, $this->fromUserQuery];
                }
                if ($this->toUserQuery) {
                    $likeConditions[] = ['like', 'tau.' . $attribute, $this->toUserQuery];
                }
            }
        }
        if (count($likeConditions) > 0) {
            $query->andWhere(['or', ...$likeConditions]);
        }

        // Context user query
        if ($this->user) {
            $query->andWhere([
                'or',
                ['fau.id' => $this->user->primaryKey],
                ['tau.id' => $this->user->primaryKey],
            ]);
        }
    }
}
