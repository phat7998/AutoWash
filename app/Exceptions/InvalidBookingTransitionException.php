<?php

declare(strict_types=1);

namespace App\Exceptions;

use RuntimeException;

final class InvalidBookingTransitionException extends RuntimeException
{
    public function __construct(string $currentStatus, string $targetStatus)
    {
        parent::__construct(sprintf(
            'Không thể chuyển lịch đặt từ trạng thái %s sang %s.',
            $currentStatus,
            $targetStatus
        ));
    }
}
