<?php

use yii\helpers\Html;
use yii\widgets\ActiveForm;
use yii\web\JqueryAsset;
use yii\jui\DatePicker;

/** @var yii\web\View $this */
/** @var frontend\models\Moving $model */
/** @var yii\widgets\ActiveForm $form */

$this->title = 'Create Moving';
$this->params['breadcrumbs'] = [['label' => 'Movings', 'url' => ['index']], ['label' => $this->title]];

JqueryAsset::register($this);
?>

<div class="moving-form form-control">

    <?php $form = ActiveForm::begin(); ?>

    <?= $form->field($model, 'Customer_name')->textInput(['id' => 'Customer_name']) ?>

    <?= $form->field($model, 'Customer_phone')->input('tel', ['maxlength' => true, 'placeholder' => '+358121314250']) ?>

    <?= $form->field($model, 'Customer_email')->input('email') ?>

    <?= $form->field($model, 'from_address')->textInput([
        'maxlength' => true,
        'id' => 'from_address',
        'placeholder' => 'Enter starting address'
    ]) ?>

    <?= $form->field($model, 'assistance')->dropDownList(['NO' => 'NO', 'YES' => 'YES'])->label('Extra Helper / Carrier') ?>

    <?= $form->field($model, 'to_address')->textInput([
        'maxlength' => true,
        'id' => 'to_address',
        'placeholder' => 'Enter destination address'
    ]) ?>

    <?= $form->field($model, 'Elevator')->dropDownList(['NO' => 'NO', 'YES' => 'YES']) ?>

    <?= $form->field($model, 'Floor_no')->textInput([
        'maxlength' => true,
        'id' => 'Floor_no',
        'placeholder' => '1,2,3,4....'
    ]) ?>

    <?= $form->field($model, 'Move_description')->textarea([
        'rows' => 6,
        'placeholder' => 'Describe the move'
    ]) ?>

    <!-- Date Picker with Saturdays Blocked -->
   <?= $form->field($model, 'Start_time')->input('datetime-local') ?>


    <div class="form-group">
        <?= Html::submitButton(Yii::t('app', 'Save'), ['class' => 'btn btn-success']) ?>
    </div>

    <?php ActiveForm::end(); ?>

</div>

<?php
// Register Google Places API Script with Autocomplete initialization
$this->registerJs("
    function initAutocomplete() {
        // Initialize Autocomplete for 'from_address' field
        var sourceAddress = new google.maps.places.Autocomplete(document.getElementById('from_address'), {
            componentRestrictions: { country: 'FI' },
            language: 'fi'
        });
        // Initialize Autocomplete for 'to_address' field
        var destinationAddress = new google.maps.places.Autocomplete(document.getElementById('to_address'), {
            componentRestrictions: { country: 'FI' },
            language: 'fi'
        });
        sourceAddress.setTypes(['geocode']);
        destinationAddress.setTypes(['geocode']);
    }

    function getCurrentAddress() {
        if (navigator.geolocation) {
            navigator.geolocation.getCurrentPosition(function(position) {
                var latLng = new google.maps.LatLng(position.coords.latitude, position.coords.longitude);
                var geocoder = new google.maps.Geocoder();
                geocoder.geocode({ 'location': latLng }, function(results, status) {
                    if (status === google.maps.GeocoderStatus.OK && results[0]) {
                        document.getElementById('from_address').value = results[0].formatted_address;
                    }
                });
            });
        }
    }

    // Load Google Maps API Script with your API Key
    function loadGoogleMapsScript() {
        var script = document.createElement('script');
        script.src = 'https://maps.googleapis.com/maps/api/js?key=' + 'AIzaSyA18grqAzap1zWB9-LDUBiDv0s2D94EkKM' + '&libraries=places&callback=initAutocomplete';
        script.async = true;
        script.defer = true;
        document.head.appendChild(script);
    }

    // Call to load Google Maps script
    loadGoogleMapsScript();
", yii\web\View::POS_END);
?>
