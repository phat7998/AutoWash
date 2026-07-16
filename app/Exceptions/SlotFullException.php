<?php

declare(strict_types=1);

namespace App\Exceptions;

use RuntimeException;

final class SlotFullException extends RuntimeException
{
    public function __construct()
    {
        parent::__construct('Khung giờ vừa hết chỗ. Vui lòng chọn khung giờ khác.');
    }
}
