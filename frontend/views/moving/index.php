<?php

use frontend\models\Moving;
use yii\helpers\Html;
use yii\helpers\Url;
use yii\grid\ActionColumn;
use yii\grid\GridView;
use yii\widgets\Pjax;
/** @var yii\web\View $this */
/** @var frontend\models\MovingSearch $searchModel */
/** @var yii\data\ActiveDataProvider $dataProvider */

$this->title = Yii::t('app', 'Movings');
$this->params['breadcrumbs'][] = $this->title;
?>
<div class="moving-index">

    <h1><?= Html::encode($this->title) ?></h1>

    <p>
        <?= Html::a(Yii::t('app', 'Create Moving'), ['create'], ['class' => 'btn btn-success']) ?>
    </p>

    <?php Pjax::begin(); ?>
    <?php // echo $this->render('_search', ['model' => $searchModel]); ?>

   
    <?= GridView::widget([
        'dataProvider' => $dataProvider,
        'filterModel' => $searchModel,
        'columns' => [
            ['class' => 'yii\grid\SerialColumn'],
             [
                'attribute' => 'Start_time',
                'label' => 'Start',
                'value' => function ($model) {
                    return date('Y-m-d l',strtotime($model->Start_time)); // Display as stored in DB (no timezone conversion)
                },
                 'format' => ['date', 'php:d-m-Y l'],
            ],
            [
                'attribute' => 'Start_time',
                'label' => 'Time',
                'value' => function ($model) {
                    return date('H:i', strtotime($model->Start_time)); // Extracts time part without altering timezone
                },
                //'format' => 'raw',
            ],

            // 'id',
            // 'time_created',
            'Customer_name',
            'Customer_phone',
            // 'Customer_email:email',
            'from_address',
            //'to_address',
            //'Move_description:ntext',
            //'Distance',
            //'Moving_status',
            //'payment_status',
            //'price',
            //'transaction_id',
            //'partner_id',
            
            //'End_time',
            [
                'class' => ActionColumn::className(),
               'urlCreator' => function ($action, $model, $key, $index, $column) {
                $modelName = strtolower((new \ReflectionClass($model))->getShortName()); // Get model name dynamically
                return Url::toRoute([$modelName . '/' . $action, 'id' => $model->id]); // Construct the URL dynamically
            }
            ],
        ],
    ]); ?>

    <?php Pjax::end(); ?>
    

</div>
