<?php

declare(strict_types=1);

namespace App\Exceptions;

final class RewardNotFoundException extends HttpException
{
    public function __construct(string $message = 'Không tìm thấy phần thưởng được yêu cầu.')
    {
        parent::__construct(404, $message);
    }
}
