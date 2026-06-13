<?php

use common\models\User;
use common\models\Customer;
use common\models\Vehicle;
use common\models\Booking;
use common\models\Constant;
use yii\helpers\Html;

$this->title = Yii::t('app', 'Dashboard');
$this->params['breadcrumbs'] = [['label' => $this->title]];

/**
 * @var User $user
 */
$user = \Yii::$app->user->identity;

/* --- LOGIC TRUY VẤN DỮ LIỆU --- */
$totalCustomer = Customer::find()->count();
$totalVehicle = Vehicle::find()->count();
$totalBooking = Booking::find()->count();

$statOverview = $statOverview ?? [];
$statFilter = $statFilter ?? ['period' => 'day', 'date' => date('Y-m-d')];
$totalShippingFee = 0;
$totalCod = 0;

/* --- LOGIC LỜI CHÀO THEO GIỜ --- */
$hour = (int)date('G');
$greetingText = 'Chào buổi tối';
$greetingIcon = '<i class="fas fa-moon"></i>';

if ($hour >= 5 && $hour <= 10) { $greetingText = 'Chào buổi sáng'; $greetingIcon = '<i class="fas fa-sun"></i>'; }
elseif ($hour >= 11 && $hour <= 13) { $greetingText = 'Chào buổi trưa'; $greetingIcon = '<i class="fas fa-certificate"></i>'; }
elseif ($hour >= 14 && $hour <= 17) { $greetingText = 'Chào buổi chiều'; $greetingIcon = '<i class="fas fa-cloud-sun"></i>'; }

$css = <<<CSS
.content-wrapper,
.content-header,
.content {
    background: #4C138B !important;
}

.dashboard-v2 {
    font-family: 'Inter', "Segoe UI", Roboto, sans-serif;
    padding: 0 6px 30px;
}

.dashboard-filter {
    background: #ffffff;
    border-radius: 10px;
    padding: 12px 14px;
    box-shadow: 0 2px 12px rgba(15, 23, 42, 0.05);
}


.dash-card {
    background: #ffffff;
    border-radius: 16px;
    border: none;
    box-shadow: 0 4px 20px rgba(0, 0, 0, 0.03);
    padding: 24px;
    height: 100%;
    transition: transform 0.3s ease, box-shadow 0.3s ease;
    position: relative;
    overflow: hidden;
}
.dash-card:hover {
    transform: translateY(-4px);
    box-shadow: 0 12px 25px rgba(0, 0, 0, 0.06);
}

/* FIX MẠNH: Tránh dính các thẻ vào nhau theo chiều dọc trên màn hình Mobile */
@media (max-width: 991px) {
    .dash-card:not(.welcome-card) {
        margin-bottom: 24px !important;
    }
}


.welcome-card {
    background: linear-gradient(135deg, #6d28d9 0%, #8b5cf6 100%);
    color: white;
    box-shadow: 0 8px 24px rgba(109, 40, 217, 0.24);
    padding: 14px 24px;
    z-index: 1;
    display: flex;
    align-items: center;
    justify-content: center;
}

.welcome-card .greeting-badge {
    display: inline-flex;
    align-items: center;
    background: rgba(255, 255, 255, 0.2); 
    color: #ffffff; 
    padding: 5px 14px;
    border-radius: 30px;
    font-size: 0.85rem;
    font-weight: 600;
}

.welcome-illust {
    position: absolute;
    right: 15px;
    bottom: -25px;
    font-size: 6.5rem;
    color: rgba(255, 255, 255, 0.1); /* Icon mờ đơn giản */
    transform: rotate(-10deg);
    pointer-events: none;
}

@keyframes floatAdmin {
    0% { transform: translateY(0); }
    50% { transform: translateY(-4px); }
    100% { transform: translateY(0); }
}
.admin-icon {
    display: inline-block;
    animation: floatAdmin 3s ease-in-out infinite;
}

/* ------------------------------------ */

.section-header {
    display: inline-flex;
    align-items: center;
    gap: 10px;
    font-size: 1.1rem;
    font-weight: 800;
    color: #1e293b;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    margin: 35px 0 20px 0;
    padding: 8px 18px;
    background: #ffffff;
    border-radius: 8px;
    box-shadow: 0 2px 10px rgba(0,0,0,0.04);
    border-left: 5px solid #7c3aed;
}

.icon-box {
    width: 54px;
    height: 54px;
    border-radius: 14px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.5rem;
}
.icon-blue { background: #f5f3ff; color: #7c3aed; }
.icon-orange { background: #fff7ed; color: #f97316; }
.icon-green { background: #f0fdf4; color: #22c55e; }
.icon-purple { background: #faf5ff; color: #a855f7; }
.icon-rose { background: #fff1f2; color: #f43f5e; }
.icon-teal { background: #f0fdfa; color: #14b8a6; }

.stat-label {
    font-size: 0.85rem;
    font-weight: 700;
    color: #64748b;
    text-transform: uppercase;
    margin-bottom: 4px;
}
.stat-value {
    font-size: 1.75rem;
    font-weight: 800;
    color: #0f172a;
    line-height: 1.2;
}

.finance-card {
    border-left: 4px solid transparent;
}
.border-l-green { border-left-color: #7c3aed; }
.border-l-orange { border-left-color: #a855f7; }
CSS;
$this->registerCss($css);
?>

<div class="dashboard-v2 container-fluid px-0">

    <div class="row mb-3">
        <div class="col-12">
            <div class="dash-card welcome-card">
                <div class="d-flex flex-column flex-md-row align-items-center justify-content-center gap-3 position-relative z-1 w-100">
                    <div class="greeting-badge m-0">
                        <?= $greetingIcon ?> <span class="ms-1"><?= $greetingText ?></span>
                    </div>
                    
                    <div class="d-flex flex-column flex-md-row align-items-center gap-2">
                        <h5 class="fw-bolder m-0 text-white" style="font-size: 1.2rem;">Xin chào, <?= Html::encode($user->username) ?>! <span class="admin-icon">👑</span></h5>
                        <span class="text-white-50 d-none d-lg-inline mx-2" style="font-size: 1.2rem;">•</span>
                        <span class="d-none d-lg-inline" style="font-size: 0.95rem; color: rgba(255, 255, 255, 0.85);">Chúc bạn một ngày quản trị hệ thống hiệu quả!</span>
                    </div>
                </div>
                <i class="fas fa-chess-king welcome-illust"></i>
            </div>
        </div>
    </div>

    <div class="row mb-4">
        <div class="col-12">
            <div class="dashboard-filter">
                <?= Html::beginForm(['site/index'], 'get', ['class' => 'form-inline mb-0']) ?>
                <?= Html::dropDownList('period', $statFilter['period'], [
                    'day' => Yii::t('app', 'Day'),
                    'month' => Yii::t('app', 'Month'),
                    'year' => Yii::t('app', 'Year'),
                ], ['class' => 'form-control form-control-sm mr-2']) ?>
                <?= Html::textInput('date', $statFilter['date'], ['class' => 'form-control form-control-sm mr-2', 'placeholder' => 'YYYY-MM-DD']) ?>
                <?= Html::submitButton(Yii::t('app', 'Filter'), ['class' => 'btn btn-primary btn-sm']) ?>
                <?= Html::endForm() ?>
            </div>
        </div>
    </div>

    <div class="row g-4 mb-4">
        <div class="col-lg-6 mb-4 mb-lg-0">
            <div class="dash-card finance-card border-l-green">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <div class="stat-label">Tổng doanh thu</div>
                        <div class="d-flex align-items-baseline mt-1">
                            <span class="stat-value text-success p-counter" data-value="<?= $totalShippingFee ?>">0</span>
                            <span class="ms-1 fw-bold text-success fs-5">₫</span>
                        </div>
                    </div>
                    <div class="icon-box icon-green" style="width: 64px; height: 64px; font-size: 2rem;"><i class="fas fa-hand-holding-usd"></i></div>
                </div>
            </div>
        </div>
        <div class="col-lg-6 mb-4 mb-lg-0">
            <div class="dash-card finance-card border-l-orange">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <div class="stat-label">Tổng COD</div>
                        <div class="d-flex align-items-baseline mt-1">
                            <span class="stat-value text-warning p-counter" data-value="<?= $totalCod ?>">0</span>
                            <span class="ms-1 fw-bold text-warning fs-5">₫</span>
                        </div>
                    </div>
                    <div class="icon-box icon-orange" style="width: 64px; height: 64px; font-size: 2rem;"><i class="fas fa-piggy-bank"></i></div>
                </div>
            </div>
        </div>
    </div>
        <div class="col-sm-6 col-md-3 mb-4 mb-md-0">
            <div class="dash-card">
                <div class="d-flex justify-content-between align-items-start">
                    <div>
                        <div class="stat-label"><?= Yii::t('app', 'Booking') ?></div>
                        <div class="stat-value p-counter" data-value="<?= $totalBooking ?>">0</div>
                    </div>
                    <div class="icon-box icon-teal"><i class="fas fa-money-bill"></i></div>
                </div>
            </div>
        </div>
    </div>

</div>

<?php
$js = <<<JS
const animateValue = (obj, start, end, duration) => {
    let startTimestamp = null;
    const step = (timestamp) => {
        if (!startTimestamp) startTimestamp = timestamp;
        const progress = Math.min((timestamp - startTimestamp) / duration, 1);
        const easeProgress = progress * (2 - progress); 
        const current = Math.round(easeProgress * (end - start) + start);
        obj.innerHTML = current.toLocaleString('vi-VN');
        if (progress < 1) {
            window.requestAnimationFrame(step);
        } else {
            obj.innerHTML = end.toLocaleString('vi-VN');
        }
    };
    if (start !== end) {
        window.requestAnimationFrame(step);
    } else {
        obj.innerHTML = end.toLocaleString('vi-VN');
    }
};

const observer = new IntersectionObserver((entries) => {
    entries.forEach(entry => {
        if (entry.isIntersecting) {
            const target = entry.target;
            const value = parseInt(target.getAttribute('data-value')) || 0;
            animateValue(target, 0, value, 1200); 
            observer.unobserve(target); 
        }
    });
}, { threshold: 0.1 });

document.querySelectorAll('.p-counter').forEach(counter => {
    observer.observe(counter);
});
JS;
$this->registerJs($js);
?>
