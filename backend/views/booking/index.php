<?php
use yii\helpers\Html;

/* @var $this yii\web\View */
/* @var $models common\models\Booking[] */

$this->title = 'Quản lý Đặt lịch (Booking)';
$this->params['breadcrumbs'][] = $this->title;
?>
<div class="booking-index">
    <h1><?= Html::encode($this->title) ?></h1>
    
    <table class="table table-bordered">
        <thead>
            <tr>
                <th>ID</th>
                <th>Mã Booking</th>
                <th>Khách hàng</th>
                <th>Biển số xe</th>
                <th>Thời gian hẹn</th>
                <th>Trạng thái</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($models as $model): ?>
            <tr>
                <td><?= $model->id ?></td>
                <td><?= Html::encode($model->booking_code) ?></td>
                <td><?= Html::encode($model->customer ? $model->customer->full_name : '') ?></td>
                <td><?= Html::encode($model->vehicle ? $model->vehicle->license_plate : '') ?></td>
                <td><?= date('Y-m-d H:i:s', $model->scheduled_at) ?></td>
                <td><?= Html::encode($model->status) ?></td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>
