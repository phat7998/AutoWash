<?php
use yii\helpers\Html;

/* @var $this yii\web\View */
/* @var $models common\models\TierRule[] */

$this->title = 'Quản lý Hạng thẻ';
$this->params['breadcrumbs'][] = $this->title;
?>
<div class="tier-rule-index">
    <h1><?= Html::encode($this->title) ?></h1>
    
    <table class="table table-bordered">
        <thead>
            <tr>
                <th>ID</th>
                <th>Mã</th>
                <th>Tên Hạng</th>
                <th>Mức tiêu tối thiểu</th>
                <th>Window đặt lịch (ngày)</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($models as $model): ?>
            <tr>
                <td><?= $model->id ?></td>
                <td><?= Html::encode($model->code) ?></td>
                <td><?= Html::encode($model->name) ?></td>
                <td><?= number_format($model->minimum_spend) ?></td>
                <td><?= $model->booking_window_days ?></td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>
