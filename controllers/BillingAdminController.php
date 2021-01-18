<?php

namespace steroids\billing\controllers;

use steroids\billing\BillingModule;
use steroids\billing\forms\ManualOperationForm;
use steroids\billing\forms\OperationsSearch;
use steroids\billing\models\BillingCurrency;
use steroids\billing\models\BillingOperation;
use steroids\billing\operations\RollbackOperation;
use steroids\core\base\SearchModel;
use yii\web\Controller;

class BillingAdminController extends Controller
{
    public static function apiMap($baseUrl = '/api/v1/admin/billing')
    {
        return [
            'admin.billing' => [
                'items' => [
                    'get-currencies' => "GET $baseUrl/currencies",
                    'get-currency' => "GET $baseUrl/currencies/<id>",
                    'update-currency' => "POST $baseUrl/currencies/<id>",
                    'get-operations' => "GET $baseUrl/operations",
                    'rollback-operation' => "POST $baseUrl/operations/<id:\d+>/rollback",
                    'create-manual' => "POST $baseUrl/operations",
                ],
            ],
        ];
    }

    /**
     * @return SearchModel
     * @throws \yii\base\Exception
     */
    public function actionGetCurrencies()
    {
        $model = new SearchModel();
        $model->model = BillingModule::resolveClass(BillingCurrency::class);
        $model->user = false;
        $model->search(\Yii::$app->request->get());
        return $model;
    }

    /**
     * @param $id
     * @return BillingCurrency|null
     * @throws \yii\web\NotFoundHttpException
     */
    public function actionGetCurrency($id)
    {
        return BillingCurrency::findOrPanic(['id' => $id]);
    }

    /**
     * @param $id
     * @return BillingCurrency|null
     * @throws \yii\web\NotFoundHttpException
     */
    public function actionUpdateCurrency($id)
    {
        $currency = BillingCurrency::findOrPanic(['id' => $id]);
        $currency->load(\Yii::$app->request->post());
        $currency->save();
        return $currency;
    }

    /**
     */
    public function actionGetOperations()
    {
        $model = new OperationsSearch();
        $model->search(\Yii::$app->request->get());
        return $model;
    }

    /**
     * @param string $id
     * @throws \yii\web\NotFoundHttpException
     */
    public function actionRollbackOperation(string $id)
    {
        $operation = BillingOperation::findOrPanic(['id' => (int)$id]);
        (new RollbackOperation([
            'fromAccount' => $operation->toAccount,
            'toAccount' => $operation->fromAccount,
            'document' => $operation,
            'amount' => $operation->delta,
        ]))->executeOnce();
    }

    /**
     * @return ManualOperationForm
     * @throws \steroids\core\exceptions\ModelSaveException
     * @throws \yii\base\Exception
     * @throws \yii\web\NotFoundHttpException
     */
    public function actionCreateManual()
    {
        $model = new ManualOperationForm();
        $model->userId = \Yii::$app->user->id;
        $model->ipAddress = \Yii::$app->request->userIP;
        $model->load(\Yii::$app->request->post());
        $model->execute();
        return $model;
    }

}
