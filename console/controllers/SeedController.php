<?php

namespace console\controllers;

use Yii;
use yii\console\Controller;
use yii\console\ExitCode;
use common\models\User;
use common\models\Customer;
use common\models\Vehicle;
use common\models\LoyaltyAccount;
use common\models\PointTransaction;
use common\models\Booking;
use common\models\TierRule;

/**
 * SeedController — tạo dữ liệu mẫu cho AutoWash
 * TV4 - Tuần 6
 *
 * Cách chạy:
 *   php yii seed          (seed 80 users)
 *   php yii seed/fresh    (xóa data seed cũ rồi seed lại)
 *   php yii seed/status   (xem thống kê data hiện tại)
 */
class SeedController extends Controller
{
    // ── Cấu hình số lượng ─────────────────────────────────────────────────────
    private int $totalUsers = 80;

    // Phân bổ tier: 40% Member, 30% Silver, 20% Gold, 10% Platinum
    private array $tierDistribution = [
        'MEMBER'   => 40,
        'SILVER'   => 30,
        'GOLD'     => 20,
        'PLATINUM' => 10,
    ];

    // Số booking theo tier (min, max)
    private array $bookingRange = [
        'MEMBER'   => [2, 6],
        'SILVER'   => [7, 14],
        'GOLD'     => [15, 22],
        'PLATINUM' => [23, 35],
    ];

    // ── Data ngẫu nhiên ────────────────────────────────────────────────────────
    private array $firstNames = [
        'Minh', 'Huy', 'Tuấn', 'Khoa', 'Nam', 'Phát', 'Đức', 'Long', 'Bình', 'Thành',
        'Linh', 'Hương', 'Thảo', 'Ngân', 'Mai', 'Lan', 'Hà', 'Thu', 'Nhung', 'Trang',
        'Quân', 'Dũng', 'Tiến', 'Hoàng', 'Khánh', 'Trung', 'Việt', 'Sơn', 'Tùng', 'Anh',
    ];

    private array $lastNames = [
        'Nguyễn', 'Trần', 'Lê', 'Phạm', 'Hoàng', 'Huỳnh', 'Võ', 'Đặng', 'Bùi', 'Đỗ',
        'Hồ', 'Ngô', 'Dương', 'Lý', 'Phan', 'Vũ', 'Đào', 'Mai', 'Đinh', 'Tô',
    ];

    private array $vehicleBrands = [
        'Honda Wave Alpha', 'Honda Air Blade', 'Honda Vision', 'Honda SH 125i',
        'Yamaha Exciter 155', 'Yamaha Grande Hybrid', 'Yamaha Sirius', 'Yamaha NVX',
        'Suzuki Raider R150', 'Suzuki GSX-S150', 'Honda Future', 'SYM Attila Elizabeth',
    ];

    private array $phonePrefix = [
        '032', '033', '034', '035', '036', '037', '038', '039',
        '096', '097', '098', '086', '089', '090', '091', '094',
    ];

    private array $provinceCodes = [
        '51', '52', '53', '54', '55', '56', '57', '58', '59',
        '61', '70', '71', '72', '79', '43', '92', '93', '95',
    ];

    // Giờ rửa xe trong ngày (giờ làm việc thực tế)
    private array $washHours = [
        7, 7, 8, 8, 8, 9, 9, 9, 10, 10,
        14, 14, 15, 15, 16, 16, 17, 17, 18,
    ];

    // Giá dịch vụ theo tier (mô phỏng 3 gói)
    private array $servicePrices = [30000, 50000, 80000];

    // ── actionIndex: seed mặc định ─────────────────────────────────────────────
    public function actionIndex(): int
    {
        $this->stdout("=== AutoWash SeedController ===\n");
        $this->stdout("Tạo $this->totalUsers users với loyalty data...\n\n");
        return $this->runSeed();
    }

    // ── actionFresh: xóa seed cũ rồi seed lại ─────────────────────────────────
    public function actionFresh(): int
    {
        $this->stdout("=== AutoWash SeedController [FRESH] ===\n");
        $this->stdout("Xóa data seed cũ...\n");
        $this->deleteSeedData();
        $this->stdout("Tạo mới $this->totalUsers users...\n\n");
        return $this->runSeed();
    }

    // ── actionStatus: xem thống kê ────────────────────────────────────────────
    public function actionStatus(): int
    {
        $this->stdout("=== Thống kê DB hiện tại ===\n");

        $db = Yii::$app->db;
        $users     = $db->createCommand("SELECT COUNT(*) FROM {{%user}} WHERE role = 'CUSTOMER'")->queryScalar();
        $customers = $db->createCommand("SELECT COUNT(*) FROM {{%customer}}")->queryScalar();
        $vehicles  = $db->createCommand("SELECT COUNT(*) FROM {{%vehicle}}")->queryScalar();
        $bookings  = $db->createCommand("SELECT COUNT(*) FROM {{%booking}}")->queryScalar();
        $loyalty   = $db->createCommand("SELECT COUNT(*) FROM {{%loyalty_account}}")->queryScalar();

        $this->stdout("Users (CUSTOMER):  $users\n");
        $this->stdout("Customers:         $customers\n");
        $this->stdout("Vehicles:          $vehicles\n");
        $this->stdout("Bookings:          $bookings\n");
        $this->stdout("Loyalty accounts:  $loyalty\n\n");

        // Phân bổ tier
        $tiers = $db->createCommand("
            SELECT tr.code, tr.name, COUNT(la.id) as cnt
            FROM {{%tier_rule}} tr
            LEFT JOIN {{%loyalty_account}} la ON la.tier_rule_id = tr.id
            GROUP BY tr.id, tr.code, tr.name
            ORDER BY tr.priority_order
        ")->queryAll();

        $this->stdout("Phân bổ tier:\n");
        foreach ($tiers as $row) {
            $this->stdout("  {$row['name']}: {$row['cnt']} accounts\n");
        }

        return ExitCode::OK;
    }

    // ── Core seed logic ────────────────────────────────────────────────────────
    private function runSeed(): int
    {
        // Bước 1: lấy TierRules từ DB
        $tierRules = TierRule::find()->orderBy(['priority_order' => SORT_ASC])->all();
        if (empty($tierRules)) {
            $this->stdout("[LỖI] Không có TierRule trong DB.\n");
            $this->stdout("       Chạy migration trước: php yii migrate\n");
            return ExitCode::DATAERR;
        }

        // Map: code => TierRule object
        $tierMap = [];
        foreach ($tierRules as $tr) {
            $tierMap[$tr->code] = $tr;
        }

        // Bước 2: tạo danh sách tier cho từng user theo tỉ lệ
        $tierAssignments = $this->buildTierAssignments();

        // Bước 3: seed từng user
        $now = time();
        $stats = ['users' => 0, 'vehicles' => 0, 'bookings' => 0, 'loyalty' => 0];
        $usedUsernames = [];
        $usedPhones    = [];
        $usedPlates    = [];

        foreach ($tierAssignments as $idx => $tierCode) {
            $tierRule = $tierMap[$tierCode] ?? $tierMap['MEMBER'];

            // ── User ──────────────────────────────────────────────────────────
            $firstName = $this->randItem($this->firstNames);
            $lastName  = $this->randItem($this->lastNames);
            $username  = $this->uniqueUsername($firstName, $idx, $usedUsernames);
            $phone     = $this->uniquePhone($usedPhones);
            $email     = $username . '@seed.autowash.test';

            $user = new User();
            $user->username      = $username;
            $user->password_hash = Yii::$app->security->generatePasswordHash('password123');
            $user->auth_key      = Yii::$app->security->generateRandomString();
            $user->role          = User::ROLE_CUSTOMER;
            $user->phone         = $phone;
            $user->email         = $email;
            $user->status        = 'ACTIVE';
            $user->created_at    = $this->randTimestamp('2024-01-01', '2025-06-01');
            $user->updated_at    = $user->created_at;

            if (!$user->save()) {
                $this->stdout("[SKIP] User $username: " . json_encode($user->errors) . "\n");
                continue;
            }

            // ── Customer ──────────────────────────────────────────────────────
            $customer = new Customer();
            $customer->user_id    = $user->id;
            $customer->full_name  = $lastName . ' ' . $firstName;
            $customer->phone      = $phone;
            $customer->created_at = $user->created_at;
            $customer->updated_at = $user->created_at;

            if (!$customer->save()) {
                $this->stdout("[SKIP] Customer for user $username: " . json_encode($customer->errors) . "\n");
                $user->delete();
                continue;
            }

            $stats['users']++;

            // ── Vehicles (1-2 xe) ─────────────────────────────────────────────
            $numVehicles = rand(1, 2);
            $vehicleIds  = [];

            for ($v = 0; $v < $numVehicles; $v++) {
                $plate = $this->uniquePlate($usedPlates);

                $vehicle = new Vehicle();
                $vehicle->customer_id   = $customer->id;
                $vehicle->license_plate = $plate;
                $vehicle->vehicle_type  = 'MOTORBIKE';
                $vehicle->brand_name    = $this->randItem($this->vehicleBrands);
                $vehicle->status        = 'ACTIVE';
                $vehicle->created_at    = $user->created_at;
                $vehicle->updated_at    = $user->created_at;

                if ($vehicle->save()) {
                    $vehicleIds[] = $vehicle->id;
                    $stats['vehicles']++;
                }
            }

            if (empty($vehicleIds)) {
                continue;
            }

            // ── Bookings ──────────────────────────────────────────────────────
            $range       = $this->bookingRange[$tierCode];
            $numBookings = rand($range[0], $range[1]);

            $totalSpend      = 0;
            $completedVisits = 0;
            $bookingIds      = [];

            for ($b = 0; $b < $numBookings; $b++) {
                $vehicleId    = $this->randItem($vehicleIds);
                $scheduledAt  = $this->randTimestamp('2024-03-01', '2025-06-20');
                $hour         = $this->randItem($this->washHours);
                $scheduledAt  = mktime($hour, rand(0, 59), 0,
                    (int)date('n', $scheduledAt),
                    (int)date('j', $scheduledAt),
                    (int)date('Y', $scheduledAt)
                );
                $serviceAmount = $this->randItem($this->servicePrices);
                $status        = $this->randomBookingStatus($tierCode);

                $earnedPoints   = 0;
                $redeemedPoints = 0;

                if ($status === 'COMPLETED') {
                    $totalSpend      += $serviceAmount;
                    $completedVisits += 1;
                    $earnedPoints     = (int)($serviceAmount / 10000); // 10k = 1 điểm
                }

                $booking = new Booking();
                $booking->customer_id           = $customer->id;
                $booking->vehicle_id            = $vehicleId;
                $booking->booking_code          = $this->generateBookingCode($idx, $b);
                $booking->scheduled_at          = $scheduledAt;
                $booking->status                = $status;
                $booking->service_amount        = $serviceAmount;
                $booking->reward_point_earned   = $earnedPoints;
                $booking->reward_point_redeemed = $redeemedPoints;
                $booking->created_at            = $scheduledAt - rand(3600, 86400);
                $booking->updated_at            = $scheduledAt;

                if ($booking->save()) {
                    $bookingIds[] = $booking->id;
                    $stats['bookings']++;
                }
            }

            // ── LoyaltyAccount ────────────────────────────────────────────────
            $pointBalance = (int)($totalSpend / 10000);

            // Xác định tier thực tế dựa trên lifetime_spend và wash_count
            $actualTierRule = $this->resolveTier($totalSpend, $completedVisits, $tierMap);

            $loyalty = new LoyaltyAccount();
            $loyalty->customer_id   = $customer->id;
            $loyalty->tier_rule_id  = $actualTierRule->id;
            $loyalty->point_balance = $pointBalance;
            $loyalty->lifetime_spend = $totalSpend;
            $loyalty->wash_count    = $completedVisits;
            $loyalty->reviewed_at   = $now;
            $loyalty->created_at    = $user->created_at;
            $loyalty->updated_at    = $now;

            if ($loyalty->save()) {
                $stats['loyalty']++;

                // ── PointTransactions cho booking completed ──────────────────
                foreach ($bookingIds as $i => $bookingId) {
                    $bk = Booking::findOne($bookingId);
                    if (!$bk || $bk->status !== 'COMPLETED' || $bk->reward_point_earned <= 0) {
                        continue;
                    }

                    $pt = new PointTransaction();
                    $pt->loyalty_account_id = $loyalty->id;
                    $pt->transaction_type   = 'EARN';
                    $pt->points             = $bk->reward_point_earned;
                    $pt->available_points   = $pointBalance;
                    $pt->reference_id       = $bookingId;
                    $pt->description        = '[seed] Tích điểm rửa xe';
                    $pt->created_at         = $bk->scheduled_at;
                    $pt->save();
                }
            }

            // Progress mỗi 10 user
            if ($stats['users'] % 10 === 0) {
                $this->stdout("  ... đã tạo {$stats['users']} users\n");
            }
        }

        // ── Tóm tắt ───────────────────────────────────────────────────────────
        $this->stdout("\n=== KẾT QUẢ SEED ===\n");
        $this->stdout("Users tạo:            {$stats['users']}\n");
        $this->stdout("Vehicles tạo:         {$stats['vehicles']}\n");
        $this->stdout("Bookings tạo:         {$stats['bookings']}\n");
        $this->stdout("Loyalty accounts tạo: {$stats['loyalty']}\n");

        // Thống kê tier
        $this->stdout("\nPhân bổ tier thực tế:\n");
        $rows = Yii::$app->db->createCommand("
            SELECT tr.name, COUNT(la.id) as cnt
            FROM {{%loyalty_account}} la
            JOIN {{%tier_rule}} tr ON tr.id = la.tier_rule_id
            GROUP BY tr.id, tr.name
            ORDER BY tr.priority_order
        ")->queryAll();
        foreach ($rows as $row) {
            $this->stdout("  {$row['name']}: {$row['cnt']}\n");
        }

        $this->stdout("\nSeed hoàn tất!\n");
        return ExitCode::OK;
    }

    // ── Helper: xóa data seed cũ ──────────────────────────────────────────────
    private function deleteSeedData(): void
    {
        $db = Yii::$app->db;

        // Lấy user ids có email seed
        $userIds = $db->createCommand(
            "SELECT id FROM {{%user}} WHERE email LIKE '%@seed.autowash.test'"
        )->queryColumn();

        if (empty($userIds)) {
            $this->stdout("  Không có data seed cũ.\n");
            return;
        }

        $ids = implode(',', array_map('intval', $userIds));

        // Lấy customer ids
        $customerIds = $db->createCommand(
            "SELECT id FROM {{%customer}} WHERE user_id IN ($ids)"
        )->queryColumn();

        if (!empty($customerIds)) {
            $cids = implode(',', array_map('intval', $customerIds));

            // Lấy loyalty account ids
            $loyaltyIds = $db->createCommand(
                "SELECT id FROM {{%loyalty_account}} WHERE customer_id IN ($cids)"
            )->queryColumn();

            if (!empty($loyaltyIds)) {
                $lids = implode(',', array_map('intval', $loyaltyIds));
                $db->createCommand("DELETE FROM {{%point_transaction}} WHERE loyalty_account_id IN ($lids)")->execute();
            }

            $db->createCommand("DELETE FROM {{%booking}} WHERE customer_id IN ($cids)")->execute();
            $db->createCommand("DELETE FROM {{%loyalty_account}} WHERE customer_id IN ($cids)")->execute();
            $db->createCommand("DELETE FROM {{%vehicle}} WHERE customer_id IN ($cids)")->execute();
            $db->createCommand("DELETE FROM {{%customer}} WHERE id IN ($cids)")->execute();
        }

        $db->createCommand("DELETE FROM {{%user}} WHERE id IN ($ids)")->execute();
        $this->stdout("  Đã xóa " . count($userIds) . " users cũ.\n");
    }

    // ── Helper: xây dựng danh sách tier theo tỉ lệ phân bổ ──────────────────
    private function buildTierAssignments(): array
    {
        $assignments = [];
        foreach ($this->tierDistribution as $code => $percent) {
            $count = (int)round($this->totalUsers * $percent / 100);
            for ($i = 0; $i < $count; $i++) {
                $assignments[] = $code;
            }
        }
        // Đảm bảo đúng tổng
        while (count($assignments) < $this->totalUsers) {
            $assignments[] = 'MEMBER';
        }
        $assignments = array_slice($assignments, 0, $this->totalUsers);
        shuffle($assignments);
        return $assignments;
    }

    // ── Helper: xác định tier thực tế dựa theo spend + visits ─────────────────
    private function resolveTier(float $spend, int $visits, array $tierMap): TierRule
    {
        // Duyệt từ cao xuống thấp (Platinum → Gold → Silver → Member)
        $ordered = ['PLATINUM', 'GOLD', 'SILVER', 'MEMBER'];
        foreach ($ordered as $code) {
            if (!isset($tierMap[$code])) continue;
            $rule = $tierMap[$code];
            if ($spend >= $rule->minimum_spend && $visits >= $rule->minimum_visits) {
                return $rule;
            }
        }
        return $tierMap['MEMBER'];
    }

    // ── Helper: trạng thái booking theo tier (khách VIP hủy ít hơn) ───────────
    private function randomBookingStatus(string $tierCode): string
    {
        // Tỉ lệ: [COMPLETED, CANCELLED, PENDING, CONFIRMED]
        $weights = [
            'MEMBER'   => [60, 20, 10, 10],
            'SILVER'   => [70, 15, 8, 7],
            'GOLD'     => [80, 10, 5, 5],
            'PLATINUM' => [88, 5,  4, 3],
        ];
        $w = $weights[$tierCode] ?? $weights['MEMBER'];
        $rand = rand(1, 100);
        if ($rand <= $w[0])                  return 'COMPLETED';
        if ($rand <= $w[0] + $w[1])          return 'CANCELLED';
        if ($rand <= $w[0] + $w[1] + $w[2])  return 'PENDING';
        return 'CONFIRMED';
    }

    // ── Helper: random timestamp trong khoảng ─────────────────────────────────
    private function randTimestamp(string $from, string $to): int
    {
        return rand(strtotime($from), strtotime($to));
    }

    // ── Helper: chọn ngẫu nhiên từ mảng ──────────────────────────────────────
    private function randItem(array $arr): mixed
    {
        return $arr[array_rand($arr)];
    }

    // ── Helper: tạo username không trùng ─────────────────────────────────────
    private function uniqueUsername(string $firstName, int $idx, array &$used): string
    {
        $base = strtolower($this->toAscii($firstName));
        $name = $base . ($idx + 1);
        $i    = 0;
        while (in_array($name, $used)) {
            $name = $base . ($idx + 1) . '_' . (++$i);
        }
        $used[] = $name;
        return $name;
    }

    // ── Helper: tạo số điện thoại không trùng ────────────────────────────────
    private function uniquePhone(array &$used): string
    {
        do {
            $phone = $this->randItem($this->phonePrefix) . rand(1000000, 9999999);
        } while (in_array($phone, $used));
        $used[] = $phone;
        return $phone;
    }

    // ── Helper: tạo biển số không trùng ──────────────────────────────────────
    private function uniquePlate(array &$used): string
    {
        do {
            $plate = $this->randItem($this->provinceCodes) . '-'
                   . rand(10, 99) . '-'
                   . rand(10000, 99999);
        } while (in_array($plate, $used));
        $used[] = $plate;
        return $plate;
    }

    // ── Helper: tạo booking code duy nhất ────────────────────────────────────
    private function generateBookingCode(int $userIdx, int $bookIdx): string
    {
        return 'BK' . date('ymd') . str_pad((string)$userIdx, 4, '0', STR_PAD_LEFT)
             . str_pad((string)$bookIdx, 3, '0', STR_PAD_LEFT)
             . rand(10, 99);
    }

    // ── Helper: chuyển tiếng Việt có dấu sang ASCII ───────────────────────────
    private function toAscii(string $str): string
    {
        $map = [
            'à'=>'a','á'=>'a','ả'=>'a','ã'=>'a','ạ'=>'a',
            'ă'=>'a','ắ'=>'a','ằ'=>'a','ẳ'=>'a','ẵ'=>'a','ặ'=>'a',
            'â'=>'a','ấ'=>'a','ầ'=>'a','ẩ'=>'a','ẫ'=>'a','ậ'=>'a',
            'đ'=>'d',
            'è'=>'e','é'=>'e','ẻ'=>'e','ẽ'=>'e','ẹ'=>'e',
            'ê'=>'e','ế'=>'e','ề'=>'e','ể'=>'e','ễ'=>'e','ệ'=>'e',
            'ì'=>'i','í'=>'i','ỉ'=>'i','ĩ'=>'i','ị'=>'i',
            'ò'=>'o','ó'=>'o','ỏ'=>'o','õ'=>'o','ọ'=>'o',
            'ô'=>'o','ố'=>'o','ồ'=>'o','ổ'=>'o','ỗ'=>'o','ộ'=>'o',
            'ơ'=>'o','ớ'=>'o','ờ'=>'o','ở'=>'o','ỡ'=>'o','ợ'=>'o',
            'ù'=>'u','ú'=>'u','ủ'=>'u','ũ'=>'u','ụ'=>'u',
            'ư'=>'u','ứ'=>'u','ừ'=>'u','ử'=>'u','ữ'=>'u','ự'=>'u',
            'ỳ'=>'y','ý'=>'y','ỷ'=>'y','ỹ'=>'y','ỵ'=>'y',
        ];
        $lower = mb_strtolower($str, 'UTF-8');
        return strtr($lower, $map);
    }
}