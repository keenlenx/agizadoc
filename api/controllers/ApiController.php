<?php

namespace api\controllers;

use Yii;
use yii\web\Controller;
use yii\web\Response;

class ApiController extends Controller
{
    // If you want to return data as JSON
    public function behaviors()
    {
        $behaviors = parent::behaviors();
        $behaviors['contentNegotiator']['formats']['application/json'] = Response::FORMAT_JSON;
        return $behaviors;
    }

    public function actionIndex()
    {
        return ['message' => 'API is working!'];
    }

    public function actionData()
    {
        return ['status' => 'success', 'data' => ['id' => 1, 'name' => 'API Example']];
    }
}
