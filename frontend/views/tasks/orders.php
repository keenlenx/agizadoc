<?php

use frontend\models\Moving;
use frontend\models\Transport;
use yii\helpers\Html;
use yii\helpers\Url;
use yii\grid\ActionColumn;
use yii\grid\GridView;
use yii\widgets\Pjax;
/** @var yii\web\View $this */
/** @var backend\models\TasksSearch $searchModel */
/** @var yii\data\ActiveDataProvider $dataProvider */

$this->title = Yii::t('app', 'My Orders');
$this->params['breadcrumbs'][] = $this->title;
?>
<div class="my-orders container col-sm">

    <h1><?= Html::encode($this->title) ?></h1>

    <?php Pjax::begin(); ?>

    <?php // Uncomment if you want a search form at the top
    // echo $this->render('_search', ['model' => $searchModel]);
    ?>
<?= GridView::widget([
    'dataProvider' => $dataProvider,
    // 'filterModel' => $searchModel,
    'columns' => [
        [
            'label' => 'Service', // Set label for the column
            'value' => function ($model) {
                return (new \ReflectionClass($model))->getShortName(); // Get model name dynamically
            },
        ],
        [
            'attribute' => 'Start_time',
            'label' => 'Date',
            'format' => ['date', 'php:Y-m-d'],
        ],
        [
            'attribute' => 'Start_time',
            'label' => 'Time',
            'format' => ['date', 'php:H:i'],
        ],
        'from_address',
        'to_address',
        'Moving_status',
        'price',
        [
            'class' => ActionColumn::className(),
            'template' => '{view} {update}',
            'buttons' => [
                'view' => function ($url, $model, $key) {
                    return Html::a('View', $url, [
                        'class' => 'btn btn-primary btn-sm',
                        'title' => 'View Details',
                    ]);
                },
                'update' => function ($url, $model, $key) {
                    if ($model->Moving_status !== 'Cancelled' && $model->Moving_status !== 'Completed') {
                        return Html::a('Edit', $url, [
                            'class' => 'btn btn-danger btn-sm',
                            'title' => 'Edit Task',
                        ]);
                    }
                    return '';
                },
            ],
            'urlCreator' => function ($action, $model, $key, $index, $column) {
                $modelName = strtolower((new \ReflectionClass($model))->getShortName());
                return Url::toRoute([$modelName . '/' . $action, 'id' => $model->id]);
            }
        ],
    ],
    'pager' => [
        'firstPageLabel' => 'First',
        'lastPageLabel' => 'Last',
    ],
    'layout' => "{items}\n{pager}",
]); ?>
