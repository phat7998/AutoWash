<?php

declare(strict_types=1);

/** @var callable(mixed): string $e */
/** @var array<string, mixed> $customer */
/** @var list<array<string, mixed>> $rewards */
/** @var list<array<string, mixed>> $redemptions */
?>
<section class="page-header">
    <div><span class="eyebrow">LOYALTY REWARD</span><h1>Đổi điểm nhận reward</h1>
        <p>Điểm được trừ theo credit lot gần hết hạn trước. Điểm điều chỉnh không hết hạn dùng sau cùng.</p></div>
    <a class="button button-outline" href="/diem-thuong">Xem sổ điểm</a>
</section>

<?php if (isset($flashSuccess) && is_string($flashSuccess)): ?>
    <div class="notification notification-success" role="status"><strong>Thành công</strong><span><?= $e($flashSuccess) ?></span></div>
<?php endif; ?>
<?php if (isset($flashError) && is_string($flashError)): ?>
    <div class="notification notification-error" role="alert"><strong>Chưa thể đổi reward</strong><span><?= $e($flashError) ?></span></div>
<?php endif; ?>

<section class="summary-grid" aria-label="Thông tin điểm">
    <article class="summary-card"><span>Điểm khả dụng</span><strong><?= $e((string) $customer['point_balance']) ?></strong></article>
    <article class="summary-card"><span>Hạng hiện tại</span><strong><?= $e((string) $customer['tier_name']) ?></strong></article>
</section>

<section class="content-section">
    <div class="section-heading"><div><span class="eyebrow">DANH MỤC</span><h2>Reward đang hoạt động</h2></div></div>
    <?php if ($rewards === []): ?>
        <div class="empty-state"><h3>Chưa có reward khả dụng</h3><p>Admin chưa cấu hình reward đang hoạt động.</p></div>
    <?php else: ?>
        <div class="card-grid">
            <?php foreach ($rewards as $reward): ?>
                <article class="card">
                    <div class="card-heading-row"><h3><?= $e($reward['name']) ?></h3>
                        <span class="status-badge <?= $reward['tier_eligible'] ? 'status-completed' : 'status-pending' ?>">
                            <?= $reward['tier_eligible'] ? 'Đủ hạng' : 'Chưa đủ hạng' ?>
                        </span></div>
                    <p><strong><?= $e((string) $reward['points_cost']) ?> điểm</strong> · hiệu lực <?= $e((string) $reward['valid_days_after_redeem']) ?> ngày sau khi đổi.</p>
                    <?php if ($reward['minimum_tier_name'] !== null): ?><p>Hạng tối thiểu: <?= $e($reward['minimum_tier_name']) ?>.</p><?php endif; ?>
                    <?php if ($reward['vehicle_types'] !== null): ?><p>Loại xe: <?= $e($reward['vehicle_types']) ?>.</p><?php endif; ?>
                    <form method="post" action="/doi-thuong/<?= $e((string) $reward['id']) ?>">
                        <input type="hidden" name="_csrf_token" value="<?= $e($csrfToken) ?>">
                        <button class="button button-primary" type="submit"
                            <?= !$reward['tier_eligible'] || !$reward['affordable'] ? 'disabled' : '' ?>>
                            <?= !$reward['affordable'] ? 'Chưa đủ điểm' : 'Đổi reward' ?>
                        </button>
                    </form>
                </article>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</section>

<section class="content-section">
    <div class="section-heading"><div><span class="eyebrow">ĐÃ ĐỔI</span><h2>Reward của tôi</h2></div></div>
    <?php if ($redemptions === []): ?>
        <div class="empty-state"><h3>Chưa đổi reward nào</h3><p>Reward đã đổi sẽ xuất hiện ở đây và chỉ thuộc tài khoản của bạn.</p></div>
    <?php else: ?>
        <div class="table-shell"><table><thead><tr><th>Reward</th><th>Điểm</th><th>Ngày đổi</th><th>Hết hạn</th><th>Trạng thái</th></tr></thead><tbody>
        <?php foreach ($redemptions as $redemption): ?><tr>
            <td><?= $e($redemption['reward_name']) ?></td><td><?= $e((string) $redemption['points_spent']) ?></td>
            <td><?= $e($redemption['redeemed_at']) ?></td><td><?= $e($redemption['expires_at']) ?></td>
            <td><span class="status-badge"><?= $e($redemption['effective_status']) ?></span></td>
        </tr><?php endforeach; ?>
        </tbody></table></div>
    <?php endif; ?>
</section>
