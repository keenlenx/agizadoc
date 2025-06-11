<?php

namespace frontend\controllers;
use Yii;
use frontend\models\Notification;
use common\models\User;
use yii\web\Controller;

class NotificationController extends Controller
{
    public function actionIndex()
    {
        $notifications = Notification::find()
            ->where(['user_id' => Yii::$app->user->id])
            ->orderBy(['created_at' => SORT_DESC])
            ->all();

        return $this->render('index', [
            'notifications' => $notifications,
        ]);
    }

    public function actionMarkAsRead($id)
    {
        $notification = Notification::findOne($id);
        if ($notification) {
            $notification->is_read = 1;
            $notification->save();
        }

        return $this->redirect(['/notification']);
    }
   public function actionReadAll(){

        $userID = Yii::$app->user->id ?? null;
        $updatedRows = Notification::MarkAsRead($userID);

        if ($updatedRows > 0) {
            Yii::$app->session->setFlash('success', "All notifications marked as read.");
        } else {
            Yii::$app->session->setFlash('info', "No unread notifications found.");
        }

        return $this->redirect(['index']);
    }
}
