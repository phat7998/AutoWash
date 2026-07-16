<?php

declare(strict_types=1);

namespace App\Exceptions;

use DomainException;

final class InsufficientPointsException extends DomainException
{
    public function __construct()
    {
        parent::__construct('Số điểm điều chỉnh âm vượt quá số dư hiện có.');
    }
}
