<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Exceptions\ValidationException;
use App\Validation\AdminReportDateRangeValidator;
use DateTimeImmutable;
use DateTimeZone;
use PHPUnit\Framework\TestCase;

final class AdminReportDateRangeValidatorTest extends TestCase
{
    private AdminReportDateRangeValidator $validator;

    protected function setUp(): void
    {
        $this->validator = new AdminReportDateRangeValidator(new DateTimeZone('Asia/Ho_Chi_Minh'));
    }

    public function testDefaultsToTheMostRecentThirtyDays(): void
    {
        $range = $this->validator->validate(null, null);
        $from = new DateTimeImmutable($range['from_date']);
        $to = new DateTimeImmutable($range['to_date']);

        self::assertSame(29, (int) $from->diff($to)->format('%a'));
        self::assertSame($range['from_date'] . ' 00:00:00', $range['from_at']);
        self::assertSame(
            $to->modify('+1 day')->format('Y-m-d 00:00:00'),
            $range['to_exclusive']
        );
    }

    public function testRejectsInvalidDateFormatsAndNonScalarInput(): void
    {
        try {
            $this->validator->validate(['2026-07-01'], '18/07/2026');
            self::fail('Dữ liệu ngày không hợp lệ phải bị từ chối.');
        } catch (ValidationException $exception) {
            self::assertArrayHasKey('from_date', $exception->errors());
            self::assertArrayHasKey('to_date', $exception->errors());
        }
    }

    public function testRejectsReversedDateRange(): void
    {
        $this->expectException(ValidationException::class);

        $this->validator->validate('2026-07-18', '2026-07-01');
    }
}
