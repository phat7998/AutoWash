<?php

declare(strict_types=1);

namespace App\Exceptions;

use RuntimeException;

final class CancellationCutoffException extends RuntimeException
{
    public function __construct()
    {
        parent::__construct(
            'Bạn chỉ có thể tự hủy lịch khi còn ít nhất 2 giờ trước giờ bắt đầu.'
        );
    }
}
