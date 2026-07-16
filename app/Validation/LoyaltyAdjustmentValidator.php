<?php

declare(strict_types=1);

namespace App\Validation;

use App\Exceptions\ValidationException;

final readonly class LoyaltyAdjustmentValidator
{
    /** @return array{user_id: int, points: int, reason: string, source_transaction_id: int|null} */
    public function validate(
        string $userId,
        string $points,
        string $reason,
        string $sourceTransactionId = ''
    ): array {
        $errors = [];
        $userIdValue = $this->positiveInteger($userId);

        if ($userIdValue === null) {
            $errors['user_id'] = 'Khách hàng cần điều chỉnh không hợp lệ.';
        }

        $pointsValue = filter_var($points, FILTER_VALIDATE_INT);

        if ($pointsValue === false || $pointsValue === 0) {
            $errors['points'] = 'Số điểm điều chỉnh phải là số nguyên khác 0.';
        } elseif ($pointsValue < -2_000_000_000 || $pointsValue > 2_000_000_000) {
            $errors['points'] = 'Số điểm điều chỉnh vượt giới hạn cho phép.';
        }

        $reasonValue = trim($reason);

        if ($reasonValue === '') {
            $errors['reason'] = 'Lý do điều chỉnh điểm là bắt buộc.';
        } elseif (mb_strlen($reasonValue) > 1_000) {
            $errors['reason'] = 'Lý do điều chỉnh không được vượt quá 1.000 ký tự.';
        }

        $sourceValue = null;

        if (trim($sourceTransactionId) !== '') {
            $sourceValue = $this->positiveInteger($sourceTransactionId);

            if ($sourceValue === null) {
                $errors['source_transaction_id'] = 'Giao dịch nguồn không hợp lệ.';
            }
        }

        if ($errors !== []) {
            throw new ValidationException($errors);
        }

        return [
            'user_id' => $userIdValue,
            'points' => $pointsValue,
            'reason' => $reasonValue,
            'source_transaction_id' => $sourceValue,
        ];
    }

    private function positiveInteger(string $value): ?int
    {
        if (preg_match('/^[1-9][0-9]*$/', $value) !== 1) {
            return null;
        }

        $integer = filter_var($value, FILTER_VALIDATE_INT);

        return $integer === false ? null : $integer;
    }
}
