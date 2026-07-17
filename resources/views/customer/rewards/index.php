<?php

declare(strict_types=1);

use App\Support\VietnameseFormatter;

/** @var callable(mixed): string $e */
/** @var array<string, mixed> $customer */
/** @var list<array<string, mixed>> $rewards */
/** @var list<array<string, mixed>> $redemptions */
$redemptionStatusLabels = [
    'available' => 'Sẵn sàng sử dụng',
    'used' => 'Đã sử dụng',
    'expired' => 'Đã hết hạn',
    'cancelled' => 'Đã hủy',
];
?>
<section class="page-heading page-heading-actions">
    <div><p class="eyebrow dark-eyebrow">Quà tặng thành viên</p><h1>Đổi điểm nhận quà</h1><p class="lead">Chọn quà phù hợp với hạng và số điểm hiện có; điểm gần hết hạn sẽ được ưu tiên sử dụng.</p></div>
    <a class="button button-outline" href="/diem-thuong">Xem lịch sử điểm</a>
</section>

<?php if (isset($flashSuccess) && is_string($flashSuccess)): ?>
    <div class="notification notification-success" role="status"><strong>Đổi quà thành công</strong><span><?= $e($flashSuccess) ?></span></div>
<?php endif; ?>
<?php if (isset($flashError) && is_string($flashError)): ?>
    <div class="notification notification-error" role="alert"><strong>Chưa thể đổi quà</strong><span><?= $e($flashError) ?></span></div>
<?php endif; ?>

<section class="loyalty-summary-grid" aria-label="Thông tin thành viên">
    <article class="summary-card"><span>Điểm khả dụng</span><strong><?= $e(number_format((int) $customer['point_balance'], 0, ',', '.')) ?> điểm</strong><small>Điểm dùng được tại thời điểm hiện tại.</small></article>
    <article class="summary-card"><span>Hạng hiện tại</span><strong><?= $e((string) $customer['tier_name']) ?></strong><small>Một số quà có yêu cầu hạng tối thiểu.</small></article>
</section>

<section class="content-section" aria-labelledby="reward-catalog-title">
    <div class="section-heading"><h2 id="reward-catalog-title">Quà có thể đổi</h2><p>Kiểm tra số điểm, điều kiện và thời hạn trước khi xác nhận.</p></div>
    <?php if ($rewards === []): ?>
        <div class="empty-state"><h3>Chưa có quà tặng phù hợp</h3><p>Danh mục quà tặng sẽ xuất hiện khi có chương trình đang hoạt động.</p><a class="button button-outline" href="/diem-thuong">Xem điểm của tôi</a></div>
    <?php else: ?>
        <div class="card-grid">
            <?php foreach ($rewards as $reward): ?>
                <article class="card reward-card">
                    <div class="card-heading-row"><h3><?= $e($reward['name']) ?></h3><span class="status-badge <?= $reward['tier_eligible'] ? 'status-completed' : 'status-pending' ?>"><?= $reward['tier_eligible'] ? 'Đủ điều kiện hạng' : 'Chưa đủ hạng' ?></span></div>
                    <p class="reward-cost"><strong><?= $e(number_format((int) $reward['points_cost'], 0, ',', '.')) ?> điểm</strong></p>
                    <dl class="detail-list">
                        <div><dt>Hiệu lực sau khi đổi</dt><dd><?= $e((string) $reward['valid_days_after_redeem']) ?> ngày</dd></div>
                        <?php if ($reward['minimum_tier_name'] !== null): ?><div><dt>Hạng tối thiểu</dt><dd><?= $e($reward['minimum_tier_name']) ?></dd></div><?php endif; ?>
                        <?php if ($reward['vehicle_types'] !== null): ?><div><dt>Phương tiện áp dụng</dt><dd><?= $e($reward['vehicle_types']) ?></dd></div><?php endif; ?>
                    </dl>
                    <form method="post" action="/doi-thuong/<?= $e((string) $reward['id']) ?>" data-confirm="Đổi <?= $e($reward['name']) ?> với <?= $e((string) $reward['points_cost']) ?> điểm?">
                        <input type="hidden" name="_csrf_token" value="<?= $e($csrfToken) ?>">
                        <button class="button button-primary" type="submit" <?= !$reward['tier_eligible'] || !$reward['affordable'] ? 'disabled' : '' ?>><?= !$reward['affordable'] ? 'Chưa đủ điểm' : 'Đổi quà này' ?></button>
                    </form>
                </article>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</section>

<section class="content-section" aria-labelledby="my-rewards-title">
    <div class="section-heading"><h2 id="my-rewards-title">Quà của tôi</h2><p>Theo dõi quà đã đổi và thời hạn sử dụng.</p></div>
    <?php if ($redemptions === []): ?>
        <div class="empty-state compact-empty"><h3>Bạn chưa đổi quà nào</h3><p>Chọn một quà tặng ở danh mục phía trên khi đủ điểm.</p></div>
    <?php else: ?>
        <div class="table-shell"><table><caption>Danh sách quà đã đổi</caption><thead><tr><th scope="col">Quà tặng</th><th scope="col">Điểm đã dùng</th><th scope="col">Ngày đổi</th><th scope="col">Hết hạn</th><th scope="col">Trạng thái</th></tr></thead><tbody>
        <?php foreach ($redemptions as $redemption): ?><tr>
            <td data-label="Quà tặng"><?= $e($redemption['reward_name']) ?></td><td data-label="Điểm đã dùng"><?= $e(number_format((int) $redemption['points_spent'], 0, ',', '.')) ?> điểm</td>
            <td data-label="Ngày đổi"><?= $e(VietnameseFormatter::date(substr((string) $redemption['redeemed_at'], 0, 10))) ?></td><td data-label="Hết hạn"><?= $e(VietnameseFormatter::date(substr((string) $redemption['expires_at'], 0, 10))) ?></td>
            <td data-label="Trạng thái"><span class="status-badge"><?= $e($redemptionStatusLabels[$redemption['effective_status']] ?? $redemption['effective_status']) ?></span></td>
        </tr><?php endforeach; ?>
        </tbody></table></div>
    <?php endif; ?>
</section>
