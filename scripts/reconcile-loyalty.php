<?php

declare(strict_types=1);

use App\Core\Database;
use App\Repositories\LoyaltyTransactionRepository;
use App\Services\LoyaltyPointCalculator;
use App\Services\LoyaltyService;
use App\Validation\LoyaltyAdjustmentValidator;

$projectRoot = require dirname(__DIR__) . '/bootstrap/environment.php';
$appConfig = require $projectRoot . '/config/app.php';
$loyaltyConfig = require $projectRoot . '/config/loyalty.php';

$service = new LoyaltyService(
    new LoyaltyTransactionRepository(Database::connection()),
    new LoyaltyPointCalculator((int) $loyaltyConfig['point_unit_amount']),
    new LoyaltyAdjustmentValidator(),
    new DateTimeZone((string) $appConfig['timezone'])
);
$hasMismatch = false;

foreach ($service->reconciliationReport() as $row) {
    $status = $row['matches'] ? 'KHỚP' : 'LỆCH';
    printf(
        "[%s] #%d %s: cache=%d, ledger=%d\n",
        $status,
        $row['user_id'],
        $row['full_name'],
        $row['cached_balance'],
        $row['ledger_balance']
    );
    $hasMismatch = $hasMismatch || !$row['matches'];
}

exit($hasMismatch ? 1 : 0);
