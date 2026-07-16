<?php

declare(strict_types=1);

namespace App\Exceptions;

use RuntimeException;

final class DuplicateLicensePlateException extends RuntimeException
{
    public function __construct()
    {
        parent::__construct('Biển số này đã được đăng ký cho một phương tiện khác.');
    }
}
