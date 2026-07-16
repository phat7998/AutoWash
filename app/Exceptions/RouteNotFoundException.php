<?php

declare(strict_types=1);

namespace App\Exceptions;

final class RouteNotFoundException extends HttpException
{
    public function __construct()
    {
        parent::__construct(404, 'Không tìm thấy trang bạn yêu cầu.');
    }
}
