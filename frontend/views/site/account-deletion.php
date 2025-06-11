<?php
use yii\helpers\Html;
use yii\widgets\ActiveForm;
?>
<div class="account-deletion">
    <div class="mt-5 offset-lg-3 col-lg-6">
        <h1><?= Html::encode($this->title) ?></h1>

        <h2> Account Deletion </h2>
         <?php if (Yii::$app->session->hasFlash('success')): ?>
        <div class="alert alert-success">
            <?= Yii::$app->session->getFlash('success') ?>
        </div>
        <?php endif; ?>

        <?php if (Yii::$app->session->hasFlash('error')): ?>
            <div class="alert alert-danger">
                <?= Yii::$app->session->getFlash('error') ?>
            </div>
        <?php endif; ?>
        <?php $form = ActiveForm::begin(); ?>

		<?= $form->field($model, 'email')
		    ->textInput(['type' => 'email'])
		    ->label('Enter your registered email to confirm account deletion')
		     ?>


        <div class="form-group mt-5">
            <?= Html::submitButton('Send OTP', ['class' => 'btn btn-warning']) ?>
        </div>

        <?php ActiveForm::end(); ?>
    </div>
</div>
