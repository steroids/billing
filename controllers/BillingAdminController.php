<?php

namespace steroids\billing\controllers;

use yii\web\Controller;

class BillingAdminController extends Controller
{
    public static function apiMap($baseUrl = '/api/v1/admin/billing')
    {
        return [
            'admin.billing' => [
                'items' => [
                    'operations' => 'GET api/v1/auth/billing/operations',
                    'create-manual' => 'POST api/v1/auth/billing/operations',
                ],
            ],
        ];
    }

    public function actionOperations()
    {

    }

    public function actionCreateManual()
    {

    }

}
