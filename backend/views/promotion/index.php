<?php
use yii\helpers\Html;

/* @var $this yii\web\View */
/* @var $models common\models\Promotion[] */

$this->title = 'Quản lý Khuyến mãi';
$this->params['breadcrumbs'][] = $this->title;
?>
<div class="promotion-index">
    <h1><?= Html::encode($this->title) ?></h1>
    
    <table class="table table-bordered">
        <thead>
            <tr>
                <th>ID</th>
                <th>Tên khuyến mãi</th>
                <th>Mục tiêu (Hạng)</th>
                <th>Loại</th>
                <th>Giá trị</th>
                <th>Trạng thái</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($models as $model): ?>
            <tr>
                <td><?= $model->id ?></td>
                <td><?= Html::encode($model->name) ?></td>
                <td><?= Html::encode($model->target_tier) ?></td>
                <td><?= Html::encode($model->promotion_type) ?></td>
                <td><?= number_format($model->value) ?></td>
                <td><?= Html::encode($model->status) ?></td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>
