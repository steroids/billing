<?php

namespace steroids\billing\controllers;

use yii\web\Controller;

class BillingController extends Controller
{
    public static function apiMap()
    {
        return [
            'billing' => [
                'items' => [
                    //'registration' => 'POST api/v1/auth/registration',
                ],
            ],
        ];
    }

    public function actionRegistration()
    {
    }
}
