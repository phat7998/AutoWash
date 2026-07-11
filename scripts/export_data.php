<?php
/**
 * export_data.php — Export toàn bộ data từ DB ra CSV
 * TV4 - Tuần 7
 */

// ── Cấu hình DB ───────────────────────────────────────────────────────────────
// Có thể truyền bằng biến môi trường:
//   AUTOWASH_DB_HOST=localhost AUTOWASH_DB_NAME=autowash AUTOWASH_DB_USER=root AUTOWASH_DB_PASS= php scripts/export_data.php
$DB_HOST = getenv('AUTOWASH_DB_HOST') ?: 'localhost';
$DB_NAME = getenv('AUTOWASH_DB_NAME') ?: 'autowash';
$DB_USER = getenv('AUTOWASH_DB_USER') ?: 'root';
$DB_PASS = getenv('AUTOWASH_DB_PASS') ?: '';

// ── Kết nối DB ────────────────────────────────────────────────────────────────
try {
    $pdo = new PDO(
        "mysql:host=$DB_HOST;dbname=$DB_NAME;charset=utf8mb4",
        $DB_USER,
        $DB_PASS,
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );
} catch (PDOException $e) {
    die("[LỖI] Kết nối DB thất bại: " . $e->getMessage() . "\n");
}

echo "=== AutoWash Export Data ===\n\n";

// ── Tạo thư mục data/ nếu chưa có ────────────────────────────────────────────
$dataDir = __DIR__ . '/../data';
if (!is_dir($dataDir)) {
    mkdir($dataDir, 0755, true);
    echo "Tạo thư mục data/\n";
}

// ── Helper: export query ra CSV ───────────────────────────────────────────────
function exportCsv(PDO $pdo, string $filepath, string $sql, string $label): void {
    echo "Đang export $label...";
    $rows = $pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC);

    $f = fopen($filepath, 'w');
    // BOM UTF-8 để Excel đọc được tiếng Việt
    fputs($f, "\xEF\xBB\xBF");

    if (!empty($rows)) {
        fputcsv($f, array_keys($rows[0]));
        foreach ($rows as $row) {
            fputcsv($f, $row);
        }
    }
    fclose($f);
    echo " " . count($rows) . " rows → $filepath\n";
}

// ── 1. bookings.csv ───────────────────────────────────────────────────────────
exportCsv($pdo, $dataDir . '/bookings.csv', "
    SELECT
        b.id                                            AS booking_id,
        b.booking_code,
        b.customer_id,
        c.full_name                                     AS customer_name,
        tr.code                                         AS tier,
        b.vehicle_id,
        v.license_plate,
        v.brand_name,
        FROM_UNIXTIME(b.scheduled_at, '%Y-%m-%d')       AS booking_date,
        FROM_UNIXTIME(b.scheduled_at, '%H:%i')          AS booking_time,
        HOUR(FROM_UNIXTIME(b.scheduled_at))             AS hour_of_day,
        DAYOFWEEK(FROM_UNIXTIME(b.scheduled_at))        AS day_of_week,
        DAYNAME(FROM_UNIXTIME(b.scheduled_at))          AS day_name,
        CASE
            WHEN DAYOFWEEK(FROM_UNIXTIME(b.scheduled_at)) IN (1,7) THEN 1
            ELSE 0
        END                                             AS is_weekend,
        CASE
            WHEN HOUR(FROM_UNIXTIME(b.scheduled_at)) IN (8,9,10,17,18,19) THEN 1
            ELSE 0
        END                                             AS is_peak_hour,
        b.status,
        CASE WHEN b.status = 'COMPLETED' THEN 1 ELSE 0 END AS is_completed,
        CASE WHEN b.status = 'CANCELLED' THEN 1 ELSE 0 END AS is_cancelled,
        b.service_amount,
        b.reward_point_earned,
        b.reward_point_redeemed,
        CASE WHEN b.promotion_id IS NOT NULL THEN 1 ELSE 0 END AS used_promotion,
        b.promotion_id,
        la.wash_count                                   AS customer_wash_count_at_time,
        la.lifetime_spend                               AS customer_lifetime_spend,
        FROM_UNIXTIME(b.created_at, '%Y-%m-%d %H:%i')  AS created_at
    FROM booking b
    JOIN customer c ON c.id = b.customer_id
    JOIN user u ON u.id = c.user_id
    LEFT JOIN vehicle v ON v.id = b.vehicle_id
    LEFT JOIN loyalty_account la ON la.customer_id = b.customer_id
    LEFT JOIN tier_rule tr ON tr.id = la.tier_rule_id
    ORDER BY b.scheduled_at ASC
", 'bookings.csv');

// ── 2. customers.csv ──────────────────────────────────────────────────────────
exportCsv($pdo, $dataDir . '/customers.csv', "
    SELECT
        c.id                                            AS customer_id,
        u.username,
        c.full_name,
        u.phone,
        u.email,
        tr.code                                         AS tier,
        tr.name                                         AS tier_name,
        la.point_balance,
        la.lifetime_spend,
        la.wash_count,
        COUNT(b.id)                                     AS total_bookings,
        SUM(CASE WHEN b.status = 'COMPLETED' THEN 1 ELSE 0 END) AS completed_bookings,
        SUM(CASE WHEN b.status = 'CANCELLED' THEN 1 ELSE 0 END) AS cancelled_bookings,
        ROUND(
            SUM(CASE WHEN b.status = 'CANCELLED' THEN 1 ELSE 0 END)
            / NULLIF(COUNT(b.id), 0) * 100, 1
        )                                               AS cancel_rate_pct,
        SUM(CASE WHEN b.promotion_id IS NOT NULL THEN 1 ELSE 0 END) AS promo_used_count,
        ROUND(
            SUM(CASE WHEN b.promotion_id IS NOT NULL THEN 1 ELSE 0 END)
            / NULLIF(COUNT(b.id), 0) * 100, 1
        )                                               AS promo_usage_rate_pct,
        AVG(b.service_amount)                           AS avg_service_amount,
        MAX(b.service_amount)                           AS max_service_amount,
        FROM_UNIXTIME(MIN(b.scheduled_at), '%Y-%m-%d') AS first_booking_date,
        FROM_UNIXTIME(MAX(b.scheduled_at), '%Y-%m-%d') AS last_booking_date,
        DATEDIFF(
            FROM_UNIXTIME(MAX(b.scheduled_at)),
            FROM_UNIXTIME(MIN(b.scheduled_at))
        )                                               AS active_days,
        FROM_UNIXTIME(u.created_at, '%Y-%m-%d')        AS registered_date
    FROM customer c
    JOIN user u ON u.id = c.user_id
    LEFT JOIN loyalty_account la ON la.customer_id = c.id
    LEFT JOIN tier_rule tr ON tr.id = la.tier_rule_id
    LEFT JOIN booking b ON b.customer_id = c.id
    GROUP BY c.id, u.username, c.full_name, u.phone, u.email,
             tr.code, tr.name, la.point_balance, la.lifetime_spend,
             la.wash_count, u.created_at
    ORDER BY la.lifetime_spend DESC
", 'customers.csv');

// ── 3. transactions.csv ───────────────────────────────────────────────────────
exportCsv($pdo, $dataDir . '/transactions.csv', "
    SELECT
        pt.id                                           AS transaction_id,
        pt.loyalty_account_id,
        la.customer_id,
        c.full_name                                     AS customer_name,
        tr.code                                         AS tier,
        pt.transaction_type,
        pt.points,
        pt.available_points,
        pt.reference_id                                 AS booking_id,
        b.service_amount                                AS booking_amount,
        b.status                                        AS booking_status,
        pt.description,
        FROM_UNIXTIME(pt.created_at, '%Y-%m-%d %H:%i') AS created_at
    FROM point_transaction pt
    JOIN loyalty_account la ON la.id = pt.loyalty_account_id
    JOIN customer c ON c.id = la.customer_id
    LEFT JOIN tier_rule tr ON tr.id = la.tier_rule_id
    LEFT JOIN booking b ON b.id = pt.reference_id
    ORDER BY pt.created_at ASC
", 'transactions.csv');

// ── Tóm tắt ───────────────────────────────────────────────────────────────────
echo "\n=== Tóm tắt ===\n";
$totalBookings     = $pdo->query("SELECT COUNT(*) FROM booking")->fetchColumn();
$totalCustomers    = $pdo->query("SELECT COUNT(*) FROM customer")->fetchColumn();
$totalTransactions = $pdo->query("SELECT COUNT(*) FROM point_transaction")->fetchColumn();
$totalRevenue      = $pdo->query("SELECT SUM(service_amount) FROM booking WHERE status = 'COMPLETED'")->fetchColumn();

echo "Tổng bookings:      $totalBookings\n";
echo "Tổng customers:     $totalCustomers\n";
echo "Tổng transactions:  $totalTransactions\n";
echo "Tổng doanh thu:     " . number_format((float)$totalRevenue, 0, '.', ',') . " đ\n";

echo "\nPhân bổ tier:\n";
$tiers = $pdo->query("
    SELECT tr.name, COUNT(la.id) as cnt
    FROM loyalty_account la
    JOIN tier_rule tr ON tr.id = la.tier_rule_id
    GROUP BY tr.id, tr.name
    ORDER BY tr.priority_order
")->fetchAll(PDO::FETCH_ASSOC);
foreach ($tiers as $row) {
    echo "  {$row['name']}: {$row['cnt']}\n";
}

echo "\nExport hoàn tất! Files nằm trong thư mục data/\n";
