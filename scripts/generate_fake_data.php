<?php
/**
 * generate_fake_data.php — Sinh behavioral data cho AutoWash
 * TV4 - Tuần 6
 *
 * Script chạy độc lập (không qua Yii2), kết nối DB trực tiếp qua PDO.
 * Mục đích: tạo 2000-5000 booking records với behavioral patterns
 * để phục vụ phân tích ML ở tuần 8-9.
 */

// ── Cấu hình DB (sửa nếu cần) ────────────────────────────────────────────────
$DB_HOST = '103.1.239.95';
$DB_NAME = 'autowash';
$DB_USER = 'userwash';
$DB_PASS = 'FcjUwrBz3Y84c9dTzqqE';

// ── Cấu hình số lượng ─────────────────────────────────────────────────────────
$TARGET_RECORDS = 3000; // mặc định

// Parse arguments
foreach ($argv as $arg) {
    if (str_starts_with($arg, '--records=')) {
        $TARGET_RECORDS = (int)substr($arg, 10);
    }
}
$FRESH = in_array('--fresh', $argv);

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

echo "=== AutoWash Fake Data Generator ===\n";
echo "Target: $TARGET_RECORDS booking records\n\n";

// ── Xóa data fake cũ nếu --fresh ─────────────────────────────────────────────
if ($FRESH) {
    echo "[!] --fresh: xóa fake data cũ...\n";
    $fakeCustomerIds = $pdo->query(
        "SELECT c.id FROM customer c
         JOIN user u ON u.id = c.user_id
         WHERE u.email LIKE '%@fake.autowash.test'"
    )->fetchAll(PDO::FETCH_COLUMN);

    if (!empty($fakeCustomerIds)) {
        $cids = implode(',', array_map('intval', $fakeCustomerIds));
        $loyaltyIds = $pdo->query(
            "SELECT id FROM loyalty_account WHERE customer_id IN ($cids)"
        )->fetchAll(PDO::FETCH_COLUMN);

        if (!empty($loyaltyIds)) {
            $lids = implode(',', array_map('intval', $loyaltyIds));
            $pdo->exec("DELETE FROM point_transaction WHERE loyalty_account_id IN ($lids)");
        }
        $pdo->exec("DELETE FROM booking WHERE customer_id IN ($cids)");
        $pdo->exec("DELETE FROM loyalty_account WHERE customer_id IN ($cids)");
        $pdo->exec("DELETE FROM vehicle WHERE customer_id IN ($cids)");
        $pdo->exec("DELETE FROM customer WHERE id IN ($cids)");

        $userIds = $pdo->query(
            "SELECT id FROM user WHERE email LIKE '%@fake.autowash.test'"
        )->fetchAll(PDO::FETCH_COLUMN);
        if (!empty($userIds)) {
            $uids = implode(',', array_map('intval', $userIds));
            $pdo->exec("DELETE FROM user WHERE id IN ($uids)");
        }
    }
    echo "    Xong.\n\n";
}

// ── Lấy TierRules từ DB ───────────────────────────────────────────────────────
$tierRules = $pdo->query(
    "SELECT * FROM tier_rule ORDER BY priority_order ASC"
)->fetchAll(PDO::FETCH_ASSOC);

if (empty($tierRules)) {
    die("[LỖI] Không có tier_rule. Chạy migration trước.\n");
}

$tierMap = [];
foreach ($tierRules as $tr) {
    $tierMap[$tr['code']] = $tr;
}

// ── Behavioral profiles theo tier ─────────────────────────────────────────────
// Mỗi tier có pattern hành vi khác nhau để ML học được
$profiles = [
    'MEMBER' => [
        'booking_count'     => [2, 8],       // số booking ít
        'cancel_rate'       => 0.25,          // hủy nhiều
        'promo_usage_rate'  => 0.15,          // ít dùng promo
        'peak_hour_rate'    => 0.40,          // ít đặt giờ cao điểm
        'weekend_rate'      => 0.35,          // ít đặt cuối tuần
        'avg_interval_days' => [30, 90],      // đặt lịch thưa
        'service_weights'   => [60, 30, 10],  // ưa gói rẻ
        'service_amounts'   => [30000, 50000, 80000],
    ],
    'SILVER' => [
        'booking_count'     => [8, 18],
        'cancel_rate'       => 0.15,
        'promo_usage_rate'  => 0.30,
        'peak_hour_rate'    => 0.50,
        'weekend_rate'      => 0.45,
        'avg_interval_days' => [14, 35],
        'service_weights'   => [30, 50, 20],
        'service_amounts'   => [30000, 50000, 80000],
    ],
    'GOLD' => [
        'booking_count'     => [18, 30],
        'cancel_rate'       => 0.08,
        'promo_usage_rate'  => 0.50,
        'peak_hour_rate'    => 0.60,
        'weekend_rate'      => 0.55,
        'avg_interval_days' => [7, 20],
        'service_weights'   => [10, 45, 45],
        'service_amounts'   => [30000, 50000, 80000],
    ],
    'PLATINUM' => [
        'booking_count'     => [30, 50],
        'cancel_rate'       => 0.04,
        'promo_usage_rate'  => 0.70,
        'peak_hour_rate'    => 0.70,
        'weekend_rate'      => 0.65,
        'avg_interval_days' => [3, 10],
        'service_weights'   => [5, 25, 70],
        'service_amounts'   => [30000, 50000, 80000],
    ],
];

// Giờ cao điểm vs thấp điểm
$peakHours    = [8, 9, 10, 17, 18, 19];
$offPeakHours = [7, 11, 12, 13, 14, 15, 16];

// ── Tính số user cần tạo ──────────────────────────────────────────────────────
// Phân bổ tier: 40% Member, 30% Silver, 20% Gold, 10% Platinum
$tierDist = ['MEMBER' => 40, 'SILVER' => 30, 'GOLD' => 20, 'PLATINUM' => 10];

// Ước tính avg booking per user
$avgBookings = 0;
foreach ($tierDist as $code => $pct) {
    $p = $profiles[$code];
    $avg = ($p['booking_count'][0] + $p['booking_count'][1]) / 2;
    $avgBookings += $avg * $pct / 100;
}
$numUsers = (int)ceil($TARGET_RECORDS / $avgBookings);
echo "[1/5] Cần tạo ~$numUsers users để đạt $TARGET_RECORDS bookings\n";

// ── Helper functions ───────────────────────────────────────────────────────────
function randItem(array $arr): mixed {
    return $arr[array_rand($arr)];
}

function weightedRand(array $items, array $weights): mixed {
    $total = array_sum($weights);
    $rand  = mt_rand(1, $total);
    $cum   = 0;
    foreach ($items as $i => $item) {
        $cum += $weights[$i];
        if ($rand <= $cum) return $item;
    }
    return end($items);
}

function randTimestamp(string $from, string $to): int {
    return mt_rand(strtotime($from), strtotime($to));
}

function toAscii(string $str): string {
    $map = [
        'à'=>'a','á'=>'a','ả'=>'a','ã'=>'a','ạ'=>'a',
        'ă'=>'a','ắ'=>'a','ằ'=>'a','ẳ'=>'a','ẵ'=>'a','ặ'=>'a',
        'â'=>'a','ấ'=>'a','ầ'=>'a','ẩ'=>'a','ẫ'=>'a','ậ'=>'a',
        'đ'=>'d','è'=>'e','é'=>'e','ẻ'=>'e','ẽ'=>'e','ẹ'=>'e',
        'ê'=>'e','ế'=>'e','ề'=>'e','ể'=>'e','ễ'=>'e','ệ'=>'e',
        'ì'=>'i','í'=>'i','ỉ'=>'i','ĩ'=>'i','ị'=>'i',
        'ò'=>'o','ó'=>'o','ỏ'=>'o','õ'=>'o','ọ'=>'o',
        'ô'=>'o','ố'=>'o','ồ'=>'o','ổ'=>'o','ỗ'=>'o','ộ'=>'o',
        'ơ'=>'o','ớ'=>'o','ờ'=>'o','ở'=>'o','ỡ'=>'o','ợ'=>'o',
        'ù'=>'u','ú'=>'u','ủ'=>'u','ũ'=>'u','ụ'=>'u',
        'ư'=>'u','ứ'=>'u','ừ'=>'u','ử'=>'u','ữ'=>'u','ự'=>'u',
        'ỳ'=>'y','ý'=>'y','ỷ'=>'y','ỹ'=>'y','ỵ'=>'y',
    ];
    return strtr(mb_strtolower($str, 'UTF-8'), $map);
}

// ── Dữ liệu ngẫu nhiên ────────────────────────────────────────────────────────
$firstNames   = ['Minh','Huy','Tuấn','Khoa','Nam','Phát','Đức','Long','Bình','Thành',
                  'Linh','Hương','Thảo','Ngân','Mai','Lan','Hà','Thu','Nhung','Trang',
                  'Quân','Dũng','Tiến','Khánh','Trung','Việt','Sơn','Tùng','Anh','Duy'];
$lastNames    = ['Nguyễn','Trần','Lê','Phạm','Hoàng','Huỳnh','Võ','Đặng','Bùi','Đỗ',
                  'Hồ','Ngô','Dương','Lý','Phan','Vũ','Đào','Mai','Đinh','Tô'];
$brands       = ['Honda Wave Alpha','Honda Air Blade','Honda Vision','Honda SH 125i',
                  'Yamaha Exciter 155','Yamaha Grande','Yamaha Sirius','Suzuki Raider R150'];
$phonePrefix  = ['032','033','034','035','036','037','038','039','096','097','098','086','089'];
$provCodes    = ['51','52','53','54','55','56','57','58','59','61','70','71','72','79','43','92'];

$usedUsernames = [];
$usedPhones    = [];
$usedPlates    = [];
$bookingCodes  = [];

// ── Lấy promotions active ─────────────────────────────────────────────────────
$promotions = $pdo->query(
    "SELECT id, target_tier FROM promotion WHERE status = 'ACTIVE' LIMIT 20"
)->fetchAll(PDO::FETCH_ASSOC);

// ── Prepare statements ────────────────────────────────────────────────────────
$stmtUser = $pdo->prepare("
    INSERT INTO user
        (username, password_hash, auth_key, role, phone, email, status, created_at, updated_at)
    VALUES
        (:username, :password_hash, :auth_key, 'CUSTOMER', :phone, :email, 'ACTIVE', :created_at, :created_at)
");

$stmtCustomer = $pdo->prepare("
    INSERT INTO customer
        (user_id, full_name, phone, created_at, updated_at)
    VALUES
        (:user_id, :full_name, :phone, :created_at, :created_at)
");

$stmtVehicle = $pdo->prepare("
    INSERT INTO vehicle
        (customer_id, license_plate, vehicle_type, brand_name, status, created_at, updated_at)
    VALUES
        (:customer_id, :plate, 'MOTORBIKE', :brand, 'ACTIVE', :created_at, :created_at)
");

$stmtBooking = $pdo->prepare("
    INSERT INTO booking
        (customer_id, vehicle_id, booking_code, scheduled_at, status,
         service_amount, reward_point_earned, reward_point_redeemed,
         promotion_id, created_at, updated_at)
    VALUES
        (:customer_id, :vehicle_id, :booking_code, :scheduled_at, :status,
         :service_amount, :reward_point_earned, :reward_point_redeemed,
         :promotion_id, :created_at, :created_at)
");

$stmtLoyalty = $pdo->prepare("
    INSERT INTO loyalty_account
        (customer_id, tier_rule_id, point_balance, lifetime_spend, wash_count,
         reviewed_at, created_at, updated_at)
    VALUES
        (:customer_id, :tier_rule_id, :point_balance, :lifetime_spend, :wash_count,
         :reviewed_at, :created_at, :updated_at)
    ON DUPLICATE KEY UPDATE
        tier_rule_id  = VALUES(tier_rule_id),
        point_balance = VALUES(point_balance),
        lifetime_spend = VALUES(lifetime_spend),
        wash_count    = VALUES(wash_count),
        reviewed_at   = VALUES(reviewed_at),
        updated_at    = VALUES(updated_at)
");

$stmtPoint = $pdo->prepare("
    INSERT INTO point_transaction
        (loyalty_account_id, transaction_type, points, available_points,
         reference_id, description, created_at)
    VALUES
        (:loyalty_account_id, 'EARN', :points, :available_points,
         :reference_id, '[fake] Tích điểm rửa xe', :created_at)
");

// ── Xây danh sách tier assignments ───────────────────────────────────────────
$tierAssignments = [];
foreach ($tierDist as $code => $pct) {
    $count = (int)round($numUsers * $pct / 100);
    for ($i = 0; $i < $count; $i++) {
        $tierAssignments[] = $code;
    }
}
while (count($tierAssignments) < $numUsers) $tierAssignments[] = 'MEMBER';
$tierAssignments = array_slice($tierAssignments, 0, $numUsers);
shuffle($tierAssignments);

// ── Hàm resolve tier thực tế ─────────────────────────────────────────────────
function resolveTier(float $spend, int $visits, array $tierMap): array {
    foreach (['PLATINUM', 'GOLD', 'SILVER', 'MEMBER'] as $code) {
        if (!isset($tierMap[$code])) continue;
        $rule = $tierMap[$code];
        if ($spend >= $rule['minimum_spend'] && $visits >= $rule['minimum_visits']) {
            return $rule;
        }
    }
    return $tierMap['MEMBER'];
}

// ── Main loop ─────────────────────────────────────────────────────────────────
echo "[2/5] Tạo users + bookings...\n";

$stats = ['users' => 0, 'bookings' => 0, 'completed' => 0, 'cancelled' => 0];
$now   = time();

foreach ($tierAssignments as $idx => $tierCode) {
    $profile = $profiles[$tierCode];

    // ── User ──────────────────────────────────────────────────────────────────
    $firstName = randItem($firstNames);
    $lastName  = randItem($lastNames);

    // Username unique
    $base     = toAscii($firstName);
    $username = $base . ($idx + 1000);
    $i = 0;
    while (in_array($username, $usedUsernames)) $username = $base . ($idx + 1000) . '_' . (++$i);
    $usedUsernames[] = $username;

    // Phone unique
    do { $phone = randItem($phonePrefix) . mt_rand(1000000, 9999999); }
    while (in_array($phone, $usedPhones));
    $usedPhones[] = $phone;

    $email     = $username . '@fake.autowash.test';
    $createdAt = randTimestamp('2023-06-01', '2024-12-01');

    $stmtUser->execute([
        ':username'      => $username,
        ':password_hash' => password_hash('password123', PASSWORD_BCRYPT, ['cost' => 4]),
        ':auth_key'      => bin2hex(random_bytes(16)),
        ':phone'         => $phone,
        ':email'         => $email,
        ':created_at'    => $createdAt,
    ]);
    $userId = (int)$pdo->lastInsertId();

    $stmtCustomer->execute([
        ':user_id'    => $userId,
        ':full_name'  => $lastName . ' ' . $firstName,
        ':phone'      => $phone,
        ':created_at' => $createdAt,
    ]);
    $customerId = (int)$pdo->lastInsertId();
    $stats['users']++;

    // ── Vehicle ───────────────────────────────────────────────────────────────
    do { $plate = randItem($provCodes) . '-' . mt_rand(10,99) . '-' . mt_rand(10000,99999); }
    while (in_array($plate, $usedPlates));
    $usedPlates[] = $plate;

    $stmtVehicle->execute([
        ':customer_id' => $customerId,
        ':plate'       => $plate,
        ':brand'       => randItem($brands),
        ':created_at'  => $createdAt,
    ]);
    $vehicleId = (int)$pdo->lastInsertId();

    // ── Bookings ──────────────────────────────────────────────────────────────
    $numBookings   = mt_rand($profile['booking_count'][0], $profile['booking_count'][1]);
    $totalSpend    = 0;
    $washCount     = 0;
    $pointBalance  = 0;
    $loyaltyId     = null;

    // Tạo chuỗi booking theo thời gian (mô phỏng interval thực tế)
    $currentTs = $createdAt + mt_rand(86400, 604800); // bắt đầu 1-7 ngày sau khi đăng ký

    for ($b = 0; $b < $numBookings; $b++) {
        // Interval giữa các booking (behavioral pattern)
        $intervalDays = mt_rand($profile['avg_interval_days'][0], $profile['avg_interval_days'][1]);
        $currentTs   += $intervalDays * 86400 + mt_rand(-3600, 3600);

        // Không vượt quá hiện tại
        if ($currentTs > $now - 86400) break;

        // Giờ đặt: peak hay off-peak
        $isPeak = (mt_rand(1, 100) <= $profile['peak_hour_rate'] * 100);
        $hour   = $isPeak ? randItem($peakHours) : randItem($offPeakHours);

        // Ngày: cuối tuần hay thường
        $isWeekend   = (mt_rand(1, 100) <= $profile['weekend_rate'] * 100);
        $dayOfWeek   = (int)date('N', $currentTs); // 1=Mon, 7=Sun
        $scheduledAt = $currentTs;
        if ($isWeekend && $dayOfWeek < 6) {
            // Dời sang thứ 7 hoặc CN gần nhất
            $scheduledAt += (6 - $dayOfWeek) * 86400;
        }
        // Set giờ
        $scheduledAt = mktime(
            $hour, mt_rand(0, 59), 0,
            (int)date('n', $scheduledAt),
            (int)date('j', $scheduledAt),
            (int)date('Y', $scheduledAt)
        );

        // Status (behavioral: tier cao hủy ít hơn)
        $rand = mt_rand(1, 100);
        $cancelThreshold = (int)($profile['cancel_rate'] * 100);
        if ($rand <= $cancelThreshold) {
            $status = 'CANCELLED';
        } elseif ($rand <= $cancelThreshold + 5) {
            $status = 'PENDING';
        } elseif ($rand <= $cancelThreshold + 8) {
            $status = 'CONFIRMED';
        } else {
            $status = 'COMPLETED';
        }

        // Service amount (tier cao dùng gói đắt hơn)
        $serviceAmount = weightedRand(
            $profile['service_amounts'],
            $profile['service_weights']
        );

        // Promotion (tier cao dùng promo nhiều hơn)
        $promotionId = null;
        if (!empty($promotions) && mt_rand(1, 100) <= $profile['promo_usage_rate'] * 100) {
            // Ưu tiên promo đúng tier
            $tierPromos = array_filter($promotions, fn($p) => $p['target_tier'] === $tierCode || $p['target_tier'] === null);
            if (!empty($tierPromos)) {
                $promotionId = randItem($tierPromos)['id'];
            }
        }

        // Điểm tích lũy
        $earnedPoints   = 0;
        $redeemedPoints = 0;
        if ($status === 'COMPLETED') {
            $totalSpend   += $serviceAmount;
            $washCount    += 1;
            $earnedPoints  = (int)($serviceAmount / 10000);
            $pointBalance += $earnedPoints;
        }

        // Booking code unique
        do {
            $code = 'FK' . date('ymd', $scheduledAt)
                  . str_pad((string)$idx, 4, '0', STR_PAD_LEFT)
                  . str_pad((string)$b, 3, '0', STR_PAD_LEFT)
                  . mt_rand(10, 99);
        } while (in_array($code, $bookingCodes));
        $bookingCodes[] = $code;

        $createdBookingAt = $scheduledAt - mt_rand(3600, 172800); // đặt 1 giờ - 2 ngày trước

        $stmtBooking->execute([
            ':customer_id'           => $customerId,
            ':vehicle_id'            => $vehicleId,
            ':booking_code'          => $code,
            ':scheduled_at'          => $scheduledAt,
            ':status'                => $status,
            ':service_amount'        => $serviceAmount,
            ':reward_point_earned'   => $earnedPoints,
            ':reward_point_redeemed' => $redeemedPoints,
            ':promotion_id'          => $promotionId,
            ':created_at'            => $createdBookingAt,
        ]);
        $bookingId = (int)$pdo->lastInsertId();
        $stats['bookings']++;

        if ($status === 'COMPLETED') $stats['completed']++;
        if ($status === 'CANCELLED') $stats['cancelled']++;

        // Tạo loyalty account sau booking đầu tiên
        if ($loyaltyId === null) {
            $actualTier = resolveTier($totalSpend, $washCount, $tierMap);
            $stmtLoyalty->execute([
                ':customer_id'   => $customerId,
                ':tier_rule_id'  => $actualTier['id'],
                ':point_balance' => $pointBalance,
                ':lifetime_spend'=> $totalSpend,
                ':wash_count'    => $washCount,
                ':reviewed_at'   => $now,
                ':created_at'    => $createdAt,
                ':updated_at'    => $now,
            ]);
            $loyaltyId = (int)$pdo->lastInsertId();
        } else {
            // Update loyalty account
            $actualTier = resolveTier($totalSpend, $washCount, $tierMap);
            $pdo->exec("
                UPDATE loyalty_account SET
                    tier_rule_id   = {$actualTier['id']},
                    point_balance  = $pointBalance,
                    lifetime_spend = $totalSpend,
                    wash_count     = $washCount,
                    updated_at     = $now
                WHERE id = $loyaltyId
            ");
        }

        // Point transaction cho completed booking
        if ($status === 'COMPLETED' && $earnedPoints > 0 && $loyaltyId) {
            $stmtPoint->execute([
                ':loyalty_account_id' => $loyaltyId,
                ':points'             => $earnedPoints,
                ':available_points'   => $pointBalance,
                ':reference_id'       => $bookingId,
                ':created_at'         => $scheduledAt,
            ]);
        }
    }

    // Progress
    if ($stats['users'] % 50 === 0) {
        echo "  ... {$stats['users']} users / {$stats['bookings']} bookings\n";
    }
}

// ── Tóm tắt ───────────────────────────────────────────────────────────────────
echo "\n[3/5] Thống kê kết quả:\n";
echo "  Users tạo:      {$stats['users']}\n";
echo "  Bookings tạo:   {$stats['bookings']}\n";
echo "  Completed:      {$stats['completed']}\n";
echo "  Cancelled:      {$stats['cancelled']}\n";

$cancelRate = $stats['bookings'] > 0
    ? round($stats['cancelled'] / $stats['bookings'] * 100, 1) : 0;
echo "  Cancel rate:    {$cancelRate}%\n";

echo "\n[4/5] Phân bổ tier fake users:\n";
$rows = $pdo->query("
    SELECT tr.name, COUNT(la.id) as cnt
    FROM loyalty_account la
    JOIN tier_rule tr ON tr.id = la.tier_rule_id
    JOIN customer c ON c.id = la.customer_id
    JOIN user u ON u.id = c.user_id
    WHERE u.email LIKE '%@fake.autowash.test'
    GROUP BY tr.id, tr.name
    ORDER BY tr.priority_order
")->fetchAll(PDO::FETCH_ASSOC);
foreach ($rows as $row) {
    echo "  {$row['name']}: {$row['cnt']}\n";
}

// ── Export CSV ────────────────────────────────────────────────────────────────
echo "\n[5/5] Export CSV...\n";

$csvDir = __DIR__ . '/../data';
if (!is_dir($csvDir)) mkdir($csvDir, 0755, true);

// bookings.csv
$bookingRows = $pdo->query("
    SELECT
        b.id, b.booking_code, b.customer_id, b.vehicle_id,
        b.scheduled_at, b.status, b.service_amount,
        b.reward_point_earned, b.reward_point_redeemed,
        b.promotion_id,
        HOUR(FROM_UNIXTIME(b.scheduled_at)) as hour_of_day,
        DAYOFWEEK(FROM_UNIXTIME(b.scheduled_at)) as day_of_week,
        DAYNAME(FROM_UNIXTIME(b.scheduled_at)) as day_name,
        la.tier_rule_id,
        tr.code as tier_code,
        la.wash_count, la.lifetime_spend
    FROM booking b
    JOIN customer c ON c.id = b.customer_id
    JOIN user u ON u.id = c.user_id
    LEFT JOIN loyalty_account la ON la.customer_id = b.customer_id
    LEFT JOIN tier_rule tr ON tr.id = la.tier_rule_id
    WHERE u.email LIKE '%@fake.autowash.test'
    ORDER BY b.scheduled_at ASC
")->fetchAll(PDO::FETCH_ASSOC);

$bookingCsv = fopen($csvDir . '/bookings.csv', 'w');
if (!empty($bookingRows)) {
    fputcsv($bookingCsv, array_keys($bookingRows[0]));
    foreach ($bookingRows as $row) fputcsv($bookingCsv, $row);
}
fclose($bookingCsv);
echo "  Xuất data/bookings.csv (" . count($bookingRows) . " rows)\n";

// customers.csv
$customerRows = $pdo->query("
    SELECT
        c.id, u.username, u.email, u.phone,
        la.tier_rule_id, tr.code as tier_code,
        la.point_balance, la.lifetime_spend, la.wash_count,
        u.created_at
    FROM customer c
    JOIN user u ON u.id = c.user_id
    LEFT JOIN loyalty_account la ON la.customer_id = c.id
    LEFT JOIN tier_rule tr ON tr.id = la.tier_rule_id
    WHERE u.email LIKE '%@fake.autowash.test'
    ORDER BY c.id ASC
")->fetchAll(PDO::FETCH_ASSOC);

$customerCsv = fopen($csvDir . '/customers.csv', 'w');
if (!empty($customerRows)) {
    fputcsv($customerCsv, array_keys($customerRows[0]));
    foreach ($customerRows as $row) fputcsv($customerCsv, $row);
}
fclose($customerCsv);
echo "  Xuất data/customers.csv (" . count($customerRows) . " rows)\n";

echo "\nFake data generation hoàn tất!\n";