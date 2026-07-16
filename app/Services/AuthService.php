<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Logger;
use App\Core\Session;
use App\Exceptions\AuthenticationException;
use App\Exceptions\ValidationException;
use App\Repositories\UserRepository;
use App\Validation\AuthValidator;
use PDOException;
use RuntimeException;
use Throwable;

final readonly class AuthService
{
    private const LOGIN_ERROR = 'Số điện thoại hoặc mật khẩu không chính xác.';
    private const DUMMY_PASSWORD_HASH = '$2y$10$WgKL6yfeBwg7HOY9lHrvX.7y0mDwmMB7HKpWwxM1WZ4QDsBJlVbEG';

    public function __construct(
        private UserRepository $users,
        private AuthValidator $validator,
        private Session $session,
        private ?Logger $logger = null
    ) {
    }

    public function register(string $phone, string $fullName, string $password): int
    {
        $phone = trim($phone);
        $fullName = trim(preg_replace('/\s+/u', ' ', $fullName) ?? $fullName);
        $errors = $this->validator->registration($phone, $fullName, $password);

        if ($errors !== []) {
            throw new ValidationException($errors);
        }

        if ($this->users->findByPhone($phone) !== null) {
            throw new ValidationException(['phone' => 'Số điện thoại đã được sử dụng.']);
        }

        $passwordHash = password_hash($password, PASSWORD_BCRYPT);

        if (!is_string($passwordHash)) {
            throw new RuntimeException('Không thể bảo vệ mật khẩu đăng ký.');
        }

        try {
            return $this->users->createCustomer($phone, $fullName, $passwordHash);
        } catch (PDOException $exception) {
            if ($exception->getCode() === '23000') {
                throw new ValidationException(['phone' => 'Số điện thoại đã được sử dụng.']);
            }

            throw $exception;
        }
    }

    /** @return array{id: int, full_name: string, role: string} */
    public function login(string $phone, string $password): array
    {
        $phone = trim($phone);
        $errors = $this->validator->credentials($phone, $password);

        if ($errors !== []) {
            $this->logLoginFailure();
            throw new AuthenticationException(self::LOGIN_ERROR);
        }

        $user = $this->users->findByPhone($phone);
        $hash = is_array($user) ? (string) $user['password_hash'] : self::DUMMY_PASSWORD_HASH;
        $passwordMatches = password_verify($password, $hash);

        if (!$passwordMatches || !is_array($user) || $user['status'] !== 'active') {
            $this->logLoginFailure();
            throw new AuthenticationException(self::LOGIN_ERROR);
        }

        $identity = [
            'id' => (int) $user['id'],
            'full_name' => (string) $user['full_name'],
            'role' => (string) $user['role'],
        ];

        $this->users->updateLastLogin($identity['id']);
        $this->session->regenerate();
        $this->session->put('auth_user', $identity);

        return $identity;
    }

    public function logout(): void
    {
        $this->session->invalidate();
    }

    private function logLoginFailure(): void
    {
        try {
            $this->logger?->warning('Đăng nhập thất bại.', ['reason' => 'credentials_or_status']);
        } catch (Throwable) {
            // Lỗi hạ tầng log không được làm thay đổi thông báo xác thực an toàn.
        }
    }
}
