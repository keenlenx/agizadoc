<?
use yii\helpers\Html;
use yii\widgets\ActiveForm;
use yii\jui\DatePicker;
use yii\helpers\Url;
use yii\web\JsExpression;

/* @var $this yii\web\View */
/* @var $model frontend\models\Booking */
/* @var $form yii\widgets\ActiveForm */
?>

<div class="booking-form">

    <?php $form = ActiveForm::begin(); ?>

    <?= $form->field($model, 'customer_name')->textInput(['maxlength' => true]) ?>
    <?= $form->field($model, 'customer_email')->input('email') ?>
    <?= $form->field($model, 'customer_phone')->input('tel') ?>

    <!-- Start Time Picker -->
    <?= $form->field($model, 'start_time')->widget(DatePicker::class, [
        'dateFormat' => 'php:Y-m-d',
        'options' => ['class' => 'form-control'],
        'clientOptions' => [
            'beforeShowDay' => new JsExpression('function(date) {
                var day = date.getDay();
                return [day !== 6]; // Disable Saturdays (0=Sunday, 6=Saturday)
            }'),
            'changeMonth' => true,
            'changeYear' => true,
            'minDate' => 0 // Disable past dates
        ],
    ]); ?>

    <!-- No. of Hours Input -->
    <?= $form->field($model, 'no_of_hours')->dropDownList([
        1 => '1 Hour', 2 => '2 Hours', 3 => '3 Hours', 4 => '4 Hours',
        5 => '5 Hours', 6 => '6 Hours', 7 => '7 Hours', 8 => '8 Hours',
        9 => '9 Hours', 10 => '10 Hours', 11 => '11 Hours', 12 => '12 Hours'
    ]) ?>

    <div class="form-group">
        <?= Html::submitButton('Book Now', ['class' => 'btn btn-success']) ?>
    </div>

    <?php ActiveForm::end(); ?>

</div>
