<?php

declare(strict_types=1);

namespace App\Exceptions;

final class CsrfTokenMismatchException extends HttpException
{
    public function __construct()
    {
        parent::__construct(419, 'Phiên biểu mẫu đã hết hạn. Vui lòng tải lại trang và thử lại.');
    }
}
