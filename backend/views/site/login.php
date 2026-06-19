<?php

use backend\models\LoginForm;
use yii\bootstrap4\ActiveForm;
use yii\helpers\Html;
use yii\helpers\Url;

$this->title = Yii::t('app', 'Login')

/**
 * @var LoginForm $model
 *
 */

?>
<div class="card">
    <div class="card-body login-card-body">
        <div class="card-header text-center">
            <a href="/" class="h4"><img src="<?= Url::to('@web/images/logo.png') ?>" width="80%" alt="logo">
                <b class="text-danger">v2</b>
            </a>
        </div>
        <div class="card-body">
            <?php $form = ActiveForm::begin(['id' => 'login-form']) ?>
            <?= $form->field($model, 'username', [
                'options' => ['class' => 'form-group has-feedback'],
                'inputTemplate' => '{input}<div class="input-group-append"><div class="input-group-text"><span class="fas fa-envelope"></span></div></div>',
                'template' => '{beginWrapper}{input}{error}{endWrapper}',
                'wrapperOptions' => ['class' => 'input-group mb-3']
            ])
                ->label(false)
                ->textInput(['placeholder' => $model->getAttributeLabel('username')]) ?>

            <?= $form->field($model, 'password', [
                'options' => ['class' => 'form-group has-feedback'],
                'inputTemplate' => '{input}<div class="input-group-append"><div class="input-group-text"><span class="fas fa-lock"></span></div></div>',
                'template' => '{beginWrapper}{input}{error}{endWrapper}',
                'wrapperOptions' => ['class' => 'input-group mb-3']
            ])
                ->label(false)
                ->passwordInput(['placeholder' => $model->getAttributeLabel('password')]) ?>

            <?= $form->field($model, 'rememberMe')->checkbox([
                'template' => '<div class="icheck-primary">{input}{label}</div>',
                'labelOptions' => [
                    'class' => 'd-none'
                ],
                'uncheck' => null
            ]) ?>

            <?= Html::submitButton(Yii::t('app', 'Sign In'), ['class' => 'btn btn-primary btn-block']) ?>
        </div>
        <?php ActiveForm::end(); ?>
    </div>
    <!-- /.login-card-body -->
</div>