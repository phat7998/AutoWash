<?php

declare(strict_types=1);

namespace App\Exceptions;

use RuntimeException;

final class BookingConflictException extends RuntimeException
{
    public function __construct()
    {
        parent::__construct(
            'Phương tiện đã có lịch đặt đang hoạt động trong khoảng thời gian này.'
        );
    }
}
