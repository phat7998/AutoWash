<?php

declare(strict_types=1);

namespace App\Exceptions;

final class MonthlyReviewBusyException extends \DomainException
{
    public function __construct(string $reviewPeriod)
    {
        parent::__construct(
            'Kỳ xét hạng ' . $reviewPeriod . ' đang được một tiến trình khác xử lý.'
        );
    }
}
