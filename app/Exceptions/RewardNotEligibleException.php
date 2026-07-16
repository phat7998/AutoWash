<?php

declare(strict_types=1);

namespace App\Exceptions;

final class RewardNotEligibleException extends \DomainException
{
    public function __construct(string $message = 'Bạn chưa đủ điều kiện đổi phần thưởng này.')
    {
        parent::__construct($message);
    }
}
