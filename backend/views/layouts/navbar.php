<?php

use common\models\User;
use mdm\admin\components\MenuHelper;
use yii\bootstrap4\Html;
use yii\bootstrap4\Nav;
use yii\bootstrap4\NavBar;
use yii\helpers\Url;

/**
 * @var User $user
 */
$user = \Yii::$app->user->identity;

?>
<nav class="main-header navbar navbar-expand-lg navbar-light navbar-white">
    <div class="container">
        <?= Html::a(Html::img('/images/logo.png', ['class' => 'brand-image', 'alt' => 'AutoWash','style' => 'margin-right: 20px']) . '<span class="brand-text font-weight-bold text-primary">AutoWash</span>', Url::to(Yii::$app->homeUrl)) ?>
        <?= Html::button('<span class="navbar-toggler-icon"></span>', [
                'class' => "navbar-toggler order-1",
                'type' => "button",
                'data-toggle' => "collapse",
                'data-target' => "#navbarCollapse",
                'aria-controls' => "navbarCollapse",
                'aria-expanded' => "false"
        ]) ?>

        <div class="collapse navbar-collapse order-3" id="navbarCollapse">
            <?php
            echo Nav::widget([
                    'options' => ['class' => 'navbar-nav'],
                    'items' => MenuHelper::getAssignedMenu(Yii::$app->user->id)
            ]);
            ?>
        </div>
        <ul class="order-1 order-md-3 navbar-nav navbar-no-expand ml-auto">
            <li class="nav-item dropdown">
                <a class="nav-link" data-toggle="dropdown" href="#">
                    <i class="fas fa-user"></i>
                </a>
                <div class="dropdown-menu dropdown-menu dropdown-menu-right">
                    <?= Html::a('<i class="fas fa-building mr-2"></i> Hồ sơ', ['/profile'], ['class' => 'dropdown-item']) ?>
                    <?= Html::a('<i class="fas fa-key mr-2"></i> '.Yii::t('app','Change Password'), ['/user/change-password'], ['class' => 'dropdown-item']) ?>
                </div>
            </li>
            <li class="nav-item">
                <?= Html::a('<i class="fas fa-power-off"></i>', ['/site/logout'], ['data-method' => 'post', 'class' => 'nav-link']) ?>
            </li>
        </ul>
    </div>
</nav>
