<?php

declare(strict_types=1);

namespace App\Validation;

use App\Exceptions\ValidationException;

final class BookingLifecycleValidator
{
    public function cancellationReason(string $reason): string
    {
        $reason = trim($reason);

        if ($reason === '') {
            throw new ValidationException([
                'cancellation_reason' => 'Lý do hủy lịch là bắt buộc đối với quản trị viên.',
            ]);
        }

        $length = preg_match_all('/./us', $reason);

        if ($length === false || $length > 500) {
            throw new ValidationException([
                'cancellation_reason' => 'Lý do hủy lịch không được vượt quá 500 ký tự.',
            ]);
        }

        return $reason;
    }
}
