<?php
use yii\helpers\Html;
use yii\widgets\ActiveForm;
use common\models\OtpForm;
?>

<div class="Verify-otp">
    <div class="mt-5 offset-lg-3 col-lg-6">
        <h2>Verify OTP</h2>

        <?php $form = ActiveForm::begin(); ?>
        <?php $sessionEmail = Yii::$app->session->get('otp_email'); ?>
        
        <!-- Ensure email is passed along as a hidden field -->
        <?= $form->field($model, 'email')->hiddenInput()->label(false) ?>

        <?= $form->field($model, 'otp')->textInput(['maxlength' => 6])->label('Enter OTP code sent to your email ' . $sessionEmail) ?>
         <!-- Submit button -->
        <div class="form-group mt-3">
            <?= Html::submitButton('Verify OTP', ['class' => 'btn btn-primary']) ?>
        </div>

        <?= Html::a('Resend OTP', ['site/resend-otp'], [
            'class' => 'link',
            'id' => 'resend-otp-link',
            'data-method' => 'post',
            'style' => 'pointer-events: none; color: grey;' // Initially disabled
        ]); ?>

        <span id="countdown-timer" style="margin-left: 10px; color: red;">(5:00)</span>
        <span id="otp-message" style="color: green; display: none;">OTP Resent Successfully!</span>

       

        <?php ActiveForm::end(); ?>
    </div>
</div>

<?php
$script = <<< JS
// Function to start the countdown
function startCountdown(durationInSeconds, display) {
    var timer = durationInSeconds, minutes, seconds;
    var interval = setInterval(function () {
        minutes = parseInt(timer / 60, 10);
        seconds = parseInt(timer % 60, 10);
        minutes = minutes < 10 ? "0" + minutes : minutes;
        seconds = seconds < 10 ? "0" + seconds : seconds;
        display.text("(" + minutes + ":" + seconds + ")");
        
        if (--timer < 0) {
            clearInterval(interval);
            $('#resend-otp-link').css({'pointer-events': 'auto', 'color': 'blue'}); // Enable button
            display.text(""); // Remove countdown when finished
        }
    }, 1000);
}

// Start the countdown immediately on page load
$(document).ready(function () {
    startCountdown(300, $('#countdown-timer')); // 5 minutes countdown

    $('#resend-otp-link').on('click', function (e) {
        e.preventDefault();
        var link = $(this);
        
        $.post(link.attr('href'), function (response) {
            if (response.success) {
                $('#otp-message').show().text('OTP Resent Successfully!');
                link.css({'pointer-events': 'none', 'color': 'grey'}); // Disable button again
                $('#countdown-timer').text("(5:00)"); // Reset timer display
                startCountdown(300, $('#countdown-timer')); // Restart countdown
            } else {
                $('#otp-message').show().text('Error: ' + response.message);
            }
        });
    });
});
JS;
$this->registerJs($script);
?>
