<?php

declare(strict_types=1);

namespace App\Exceptions;

final class BookingNotFoundException extends HttpException
{
    public function __construct()
    {
        parent::__construct(404, 'Không tìm thấy lịch đặt được yêu cầu.');
    }
}
