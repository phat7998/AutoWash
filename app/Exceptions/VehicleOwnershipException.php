<?php

declare(strict_types=1);

namespace App\Exceptions;

final class VehicleOwnershipException extends HttpException
{
    public function __construct()
    {
        parent::__construct(404, 'Không tìm thấy phương tiện trong tài khoản của bạn.');
    }
}
