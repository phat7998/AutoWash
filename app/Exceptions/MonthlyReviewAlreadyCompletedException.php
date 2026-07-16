<?php

declare(strict_types=1);

namespace App\Exceptions;

final class MonthlyReviewAlreadyCompletedException extends \DomainException
{
    public function __construct(string $reviewPeriod)
    {
        parent::__construct('Kỳ xét hạng ' . $reviewPeriod . ' đã hoàn tất và không thể chạy lại.');
    }
}
