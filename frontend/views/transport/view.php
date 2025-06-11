<?php

use yii\helpers\Html;
use yii\widgets\DetailView;

/** @var yii\web\View $this */
/** @var frontend\models\Moving $model */

$this->title = $model->id;
$this->params['breadcrumbs'][] = ['label' => Yii::t('app', 'Transport'), 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;
\yii\web\YiiAsset::register($this);
?>
<div class="moving-view">

    <h1><?= Html::encode($this->title) ?></h1>

    <p>
        <?//= Html::a(Yii::t('app', 'Update'), ['update', 'id' => $model->id], ['class' => 'btn btn-primary']) ?>
        
        <?php if (Yii::$app->user->can('deleteItem')): ?>
    <!-- Only show the delete button if the user has the delete permission -->
        
        <?= Html::a(Yii::t('app', 'Delete'), ['delete', 'id' => $model->id], [
            'class' => 'btn btn-danger',
            'data' => [
                'confirm' => Yii::t('app', 'Are you sure you want to delete this item?'),
                'method' => 'post',
            ],
        ]) ?>

    <?php endif; ?>
    <?php if (Yii::$app->user->can('completeItem')): ?>
     <?php if ($model->Moving_status != 'Completed' and $model->Moving_status !== 'Cancelled'): ?>
    <!-- Only show the delete button if the user has the delete permission -->
        
        <?= Html::a(Yii::t('app', 'Complete'), ['complete', 'id' => $model->id], [
            'class' => 'btn btn-danger',
            'data' => [
                'confirm' => Yii::t('app', 'Are you sure you want to Complete this item?'),
                'method' => 'post',
            ],
        ]) ?>
    <?php endif; ?>
    <?php endif; ?>
    </p>

    <?= DetailView::widget([
        'model' => $model,
        'attributes' => [
            'id',
            'time_created',
            'Customer_name',
            'Customer_phone',
            'Customer_email:email',
            'from_address',
            'to_address',
            'Move_description:ntext',
            'Distance',
            'Moving_status',
            'payment_status',
            'price',
            'transaction_id',
            'partner_id',
            'assistance',
            'assistant_id',
            'Start_time',
            'End_time',
            'Stripe_code',
        ],
    ]) ?>
      <?php if ($model->payment_status !== 'Paid' && $model->payment_status !== 'Deposit'): ?>
    <?= Html::a('Make Deposit', ['pay/deposit', 'txn_id' => $model->transaction_id], ['class' => 'btn btn-primary']) ?>
    <?php endif; ?>
   <?php if ($model->payment_status !== 'Paid'): ?>
    <?= Html::a('Pay Now', ['pay/pay', 'txn_id' => $model->transaction_id], ['class' => 'btn btn-success']) ?>
    <?php endif; ?>

</div>
