<?php

use frontend\models\Notification;
use yii\helpers\Html;


$this->title = Yii::t('app', 'Notifications');
$this->params['breadcrumbs'][] = $this->title;
?>
<div class="container col-sm">

    <h3><?= Html::encode($this->title) ?></h3>
    <p>
       <? echo Html::a('Mark All', ['read-all'], ['class' => 'btn btn-primary']);
       ?>
    </p>
  
    <? foreach ($notifications as $notification): ?>
        <div class="d-flex align-items-center justify-content-between notification py-2">
            <small class="text-muted"><?= Yii::$app->formatter->asDatetime($notification->created_at) ?></small>
            <span class="flex-grow-1 mx-3"><?= Html::encode($notification->message) ?></span>
            
            <?php if (!$notification->is_read): ?>
                <?= Html::a('<i class="fa-solid fa-check"></i>', ['mark-as-read', 'id' => $notification->notifix_id], ['class' => 'text-primary']) ?>
            <?php endif; ?>
        </div>
    <?php endforeach; ?>

</div>