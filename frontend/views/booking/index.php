<?
use yii\grid\GridView;
use yii\widgets\ActiveForm;
use yii\helpers\Html;

echo GridView::widget([
    'dataProvider' => $dataProvider,
    'filterModel' => $searchModel,
    'columns' => [
        ['class' => 'yii\grid\SerialColumn'],
        'customer_name',
        'customer_email',
        'start_time',
        'end_time',
        'status',
        ['class' => 'yii\grid\ActionColumn'],
    ],
]);
