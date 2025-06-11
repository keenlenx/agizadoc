<?
// File: api/controllers/MovingController.php

namespace api\controllers;

use Yii;
use yii\rest\ActiveController;
use api\models\Moving;  // Import your Moving model if it's not already

class MovingController extends ActiveController
{
    public $modelClass = 'api\models\Moving'; // Your Moving model

    // Optionally, you can override actions like index, view, create, etc.

    public function actions()
    {
        $actions = parent::actions();
        // Customize actions here if needed (e.g., create action)
        return $actions;
    }
}
