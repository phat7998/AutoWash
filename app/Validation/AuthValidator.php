<?php

declare(strict_types=1);

namespace App\Validation;

final class AuthValidator
{
    /** @return array<string, string> */
    public function registration(string $phone, string $fullName, string $password): array
    {
        $errors = $this->credentials($phone, $password);
        $nameLength = mb_strlen($fullName);

        if ($nameLength < 2 || $nameLength > 150) {
            $errors['full_name'] = 'Họ và tên phải có từ 2 đến 150 ký tự.';
        }

        return $errors;
    }

    /** @return array<string, string> */
    public function credentials(string $phone, string $password): array
    {
        $errors = [];

        if (preg_match('/^[0-9]{9,15}$/', $phone) !== 1) {
            $errors['phone'] = 'Số điện thoại phải gồm từ 9 đến 15 chữ số.';
        }

        $passwordLength = strlen($password);

        if ($passwordLength < 8 || $passwordLength > 72) {
            $errors['password'] = 'Mật khẩu phải có từ 8 đến 72 ký tự.';
        }

        return $errors;
    }
}
