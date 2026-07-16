<?php

declare(strict_types=1);

namespace App\Exceptions;

final class LprAttemptOwnershipException extends HttpException
{
    public function __construct()
    {
        parent::__construct(404, 'Không tìm thấy lần nhận diện biển số trong tài khoản của bạn.');
    }
}
