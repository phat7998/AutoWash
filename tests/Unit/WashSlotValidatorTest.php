<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Validation\WashSlotValidator;
use DateTimeImmutable;
use DateTimeZone;
use PHPUnit\Framework\TestCase;

final class WashSlotValidatorTest extends TestCase
{
    public function testAcceptsFutureSlotAndNormalizesTime(): void
    {
        $timezone = new DateTimeZone('Asia/Ho_Chi_Minh');
        $validator = new WashSlotValidator($timezone);
        $future = (new DateTimeImmutable('today', $timezone))->modify('+1 day')->format('Y-m-d');

        self::assertSame([], $validator->validate($future, '08:00', '09:30', '5'));
        self::assertSame('08:00:00', $validator->normalizeTime('08:00'));
    }

    public function testRejectsPastInvalidPeriodAndCapacity(): void
    {
        $validator = new WashSlotValidator(new DateTimeZone('Asia/Ho_Chi_Minh'));
        $errors = $validator->validate('2020-02-30', '10:00', '09:00', '0');

        self::assertArrayHasKey('slot_date', $errors);
        self::assertArrayHasKey('end_time', $errors);
        self::assertArrayHasKey('capacity_units', $errors);
        self::assertNull($validator->normalizeTime('24:00'));
    }
}
