<?
namespace frontend\controllers;

use yii\rest\ActiveController;

class ApiController extends ActiveController
{
    public $modelClass = 'frontend\models\Moving'; // Your Moving model

    // Optionally, you can override actions like index, view, create, etc.

    public function actions()
    {
        $actions = parent::actions();
        // Customize actions here if needed (e.g., create action)
        return $actions;
    }
    
}
