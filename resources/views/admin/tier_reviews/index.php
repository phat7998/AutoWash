<?php

declare(strict_types=1);

use App\Support\VietnameseFormatter;

/** @var callable(mixed): string $e */
/** @var list<array<string, mixed>> $runs */
/** @var list<array<string, mixed>> $histories */
?>
<section class="page-header">
    <div>
        <span class="eyebrow">LOYALTY</span>
        <h1>Kết quả xét hạng hàng tháng</h1>
        <p>
            Tác vụ CLI xét tháng lịch vừa kết thúc. Mỗi khách hàng được snapshot trước khi đổi hạng
            và reset chỉ số tháng; số dư điểm không bị thay đổi.
        </p>
    </div>
</section>

<section class="content-section" aria-labelledby="review-runs-title">
    <h2 id="review-runs-title">Các lần chạy gần đây</h2>
    <?php if ($runs === []): ?>
        <div class="empty-state">
            <h3>Chưa có kỳ xét hạng</h3>
            <p>Chạy <code>php scripts/monthly-review.php</code> để xét tháng vừa kết thúc.</p>
        </div>
    <?php else: ?>
        <div class="table-shell">
            <table>
                <thead><tr><th>Kỳ</th><th>Trạng thái</th><th>Đã xử lý</th><th>Bắt đầu</th><th>Hoàn tất / lỗi</th></tr></thead>
                <tbody>
                <?php foreach ($runs as $run): ?>
                    <?php
                    $status = (string) $run['status'];
                    $statusLabel = match ($status) {
                        'completed' => 'Hoàn tất',
                        'failed' => 'Thất bại',
                        default => 'Đang chạy',
                    };
                    $statusClass = match ($status) {
                        'completed' => 'status-completed',
                        'failed' => 'status-cancelled',
                        default => 'status-pending',
                    };
                    ?>
                    <tr>
                        <td><strong><?= $e($run['review_period']) ?></strong></td>
                        <td><span class="status-badge <?= $e($statusClass) ?>"><?= $e($statusLabel) ?></span></td>
                        <td><?= $e((string) $run['processed_users']) ?> khách hàng</td>
                        <td><?= $e($run['started_at']) ?></td>
                        <td>
                            <?= $run['completed_at'] !== null ? $e($run['completed_at']) : '—' ?>
                            <?php if ($run['error_message'] !== null): ?><br><span class="field-error"><?= $e($run['error_message']) ?></span><?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</section>

<section class="content-section" aria-labelledby="tier-history-title">
    <h2 id="tier-history-title">Lịch sử xét hạng</h2>
    <?php if ($histories === []): ?>
        <div class="empty-state"><h3>Chưa có lịch sử</h3><p>Lịch sử sẽ xuất hiện sau kỳ xét đầu tiên.</p></div>
    <?php else: ?>
        <div class="table-shell">
            <table>
                <thead><tr><th>Khách hàng</th><th>Kỳ</th><th>Thay đổi</th><th>Snapshot</th><th>Lý do</th></tr></thead>
                <tbody>
                <?php foreach ($histories as $history): ?>
                    <tr>
                        <td><strong><?= $e($history['full_name']) ?></strong></td>
                        <td><?= $e($history['review_period']) ?></td>
                        <td><?= $e($history['old_tier_name']) ?> → <?= $e($history['new_tier_name']) ?></td>
                        <td><?= $e(VietnameseFormatter::vnd((string) $history['monthly_spend_snapshot'])) ?><br><?= $e((string) $history['monthly_visits_snapshot']) ?> lượt</td>
                        <td><?= $e($history['reason']) ?></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</section>
