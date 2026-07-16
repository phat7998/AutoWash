<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Validation\AuthValidator;
use PHPUnit\Framework\TestCase;

final class AuthValidatorTest extends TestCase
{
    public function testRegistrationAcceptsValidVietnameseDemoData(): void
    {
        $errors = (new AuthValidator())->registration('0901234567', 'Nguyễn Văn An', 'MatKhau@123');

        self::assertSame([], $errors);
    }

    public function testRegistrationRejectsInvalidPhoneNameAndPassword(): void
    {
        $errors = (new AuthValidator())->registration('09A', 'A', 'ngan');

        self::assertArrayHasKey('phone', $errors);
        self::assertArrayHasKey('full_name', $errors);
        self::assertArrayHasKey('password', $errors);
    }

    public function testPasswordLongerThanBcryptBoundaryIsRejected(): void
    {
        $errors = (new AuthValidator())->credentials('0901234567', str_repeat('a', 73));

        self::assertArrayHasKey('password', $errors);
    }
}
