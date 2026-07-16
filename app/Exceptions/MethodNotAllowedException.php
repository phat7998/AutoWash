<?php

declare(strict_types=1);

namespace App\Exceptions;

final class MethodNotAllowedException extends HttpException
{
    /**
     * @param list<string> $allowedMethods
     */
    public function __construct(array $allowedMethods)
    {
        parent::__construct(
            405,
            'Phương thức gửi yêu cầu không được hỗ trợ.',
            ['Allow' => implode(', ', $allowedMethods)]
        );
    }
}
