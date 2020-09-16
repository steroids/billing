<?php

namespace steroids\billing\controllers;

use steroids\billing\forms\ManualOperationForm;
use steroids\billing\forms\OperationsSearch;
use yii\web\Controller;

class BillingAdminController extends Controller
{
    public static function apiMap($baseUrl = '/api/v1/admin/billing')
    {
        return [
            'admin.billing' => [
                'items' => [
                    'get-operations' => "GET $baseUrl/operations",
                    'create-manual' => "POST $baseUrl/operations",
                ],
            ],
        ];
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
