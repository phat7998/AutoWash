<?php

declare(strict_types=1);

use App\Support\VietnameseFormatter;

/** @var Closure(mixed): string $e */
/** @var array<string, mixed> $summary */
/** @var list<array<string, mixed>> $transactions */
?>
<section class="page-heading page-heading-actions">
    <div>
        <p class="eyebrow dark-eyebrow">Khách hàng thân thiết</p>
        <h1>Điểm thưởng của tôi</h1>
        <p class="lead">Theo dõi số dư, điểm sắp hết hạn và toàn bộ thay đổi trong sổ giao dịch.</p>
    </div>
    <a class="button button-outline" href="/tai-khoan">Về tổng quan</a>
</section>

<section class="loyalty-summary-grid" aria-label="Tổng quan điểm thưởng">
    <article class="summary-card">
        <span>Số dư khả dụng</span>
        <strong><?= $e(number_format((int) $summary['point_balance'], 0, ',', '.')) ?> điểm</strong>
        <small>Số dư cache được đối chiếu với ledger.</small>
    </article>
    <article class="summary-card">
        <span>Hạng hiện tại</span>
        <strong><?= $e($summary['tier_name']) ?></strong>
        <small>Hệ số tích điểm <?= $e($summary['point_rate']) ?>.</small>
    </article>
    <article class="summary-card">
        <span>Sắp hết hạn trong 30 ngày</span>
        <strong><?= $e(number_format((int) $summary['expiring_points_30_days'], 0, ',', '.')) ?> điểm</strong>
        <small>Chỉ tính các lô earn còn điểm khả dụng.</small>
    </article>
</section>

<section class="booking-section" aria-labelledby="point-history-title">
    <div class="section-heading">
        <h2 id="point-history-title">Lịch sử điểm</h2>
        <p>Mỗi thay đổi có nguồn, mô tả và thời điểm để truy vết.</p>
    </div>
    <?php if ($transactions === []): ?>
        <div class="empty-state compact-empty">
            <h2>Chưa có giao dịch điểm</h2>
            <p>Điểm sẽ được ghi nhận khi một lịch đặt được hoàn thành hoặc khi quản trị viên điều chỉnh hợp lệ.</p>
        </div>
    <?php else: ?>
        <div class="table-shell">
            <table>
                <caption>Sổ giao dịch điểm của tài khoản</caption>
                <thead>
                    <tr>
                        <th scope="col">Loại</th>
                        <th scope="col">Thay đổi</th>
                        <th scope="col">Nội dung</th>
                        <th scope="col">Thời điểm</th>
                        <th scope="col">Hết hạn</th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($transactions as $transaction): ?>
                    <tr>
                        <td data-label="Loại"><?= $e($transaction['type_label']) ?></td>
                        <td data-label="Thay đổi" class="<?= $transaction['is_credit'] ? 'point-credit' : 'point-debit' ?>">
                            <?= $transaction['is_credit'] ? '+' : '' ?><?= $e(number_format((int) $transaction['points_delta'], 0, ',', '.')) ?> điểm
                        </td>
                        <td data-label="Nội dung">
                            <?= $e($transaction['description']) ?>
                            <?php if (isset($transaction['created_by_name'])): ?>
                                <small class="transaction-meta">Bởi <?= $e($transaction['created_by_name']) ?></small>
                            <?php endif; ?>
                        </td>
                        <td data-label="Thời điểm"><?= $e(substr((string) $transaction['created_at'], 0, 16)) ?></td>
                        <td data-label="Hết hạn">
                            <?= $transaction['expires_at'] === null
                                ? 'Không áp dụng'
                                : $e(VietnameseFormatter::date(substr((string) $transaction['expires_at'], 0, 10))) ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</section>
