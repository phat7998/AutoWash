<?php

declare(strict_types=1);

namespace App\Exceptions;

use RuntimeException;

final class BookingWindowExceededException extends RuntimeException
{
    public function __construct(int $windowDays)
    {
        parent::__construct(sprintf(
            'Hạng hiện tại chỉ cho phép đặt trước tối đa %d ngày.',
            $windowDays
        ));
    }
}
