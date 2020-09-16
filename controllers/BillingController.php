<?php

namespace steroids\billing\controllers;

use steroids\billing\forms\OperationsSearch;
use steroids\billing\models\BillingCurrency;
use yii\web\Controller;

class BillingController extends Controller
{
    public static function apiMap($baseUrl = '/api/v1/billing')
    {
        return [
            'billing' => [
                'items' => [
                    'currencies' => "GET $baseUrl/currencies",
                    'user-operations' => "GET $baseUrl/user-operations/<userId>",
                ],
            ],
        ];
    }

    /**
     * @return array
     * @throws \yii\base\InvalidConfigException
     */
    public function actionCurrencies()
    {
        return BillingCurrency::asEnum();
    }

    /**
     * @return OperationsSearch
     */
    public function actionUserOperations()
    {
        $model = new OperationsSearch();
        $model->user = \Yii::$app->user->identity;
        $model->search(\Yii::$app->request->get());
        return $model;
    }
}
