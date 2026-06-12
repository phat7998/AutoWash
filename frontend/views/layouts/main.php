<?php

use yii\helpers\Html;

?>
<?php $this->beginPage(); ?>
<!DOCTYPE html>
<html lang="<?= Yii::$app->language ?>">
<head>
    <meta charset="<?= Yii::$app->charset ?>">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= Html::encode($this->title ?: 'AutoWash') ?></title>
    <?php $this->head(); ?>
</head>
<body>
<?php $this->beginBody(); ?>
<main class="container" style="max-width: 1080px; margin: 32px auto; font-family: Arial, sans-serif;">
    <?= $content ?>
</main>
<?php $this->endBody(); ?>
</body>
</html>
<?php $this->endPage(); ?>

