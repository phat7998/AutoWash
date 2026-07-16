<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Services\LicensePlateService;
use App\Validation\VehicleValidator;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class LicensePlateServiceTest extends TestCase
{
    #[DataProvider('normalizationCases')]
    public function testNormalizesCommonCivilianPlate(string $input, string $expected): void
    {
        $plates = new LicensePlateService();

        self::assertSame($expected, $plates->normalize($input));
        self::assertTrue($plates->isCommonCivilianPlate($expected));
    }

    /** @return iterable<string, array{string, string}> */
    public static function normalizationCases(): iterable
    {
        yield 'xe máy chữ thường và dấu' => [' 59a-123.45 ', '59A12345'];
        yield 'ô tô hai chữ cái' => ['51 ab 12345', '51AB12345'];
        yield 'xe tải bốn số cuối' => ['50C-1234', '50C1234'];
        yield 'xe khách' => ['29d.56789', '29D56789'];
    }

    #[DataProvider('invalidCases')]
    public function testRejectsInvalidOrOutOfScopePlate(string $input): void
    {
        $plates = new LicensePlateService();

        self::assertFalse($plates->isCommonCivilianPlate($plates->normalize($input)));
    }

    /** @return iterable<string, array{string}> */
    public static function invalidCases(): iterable
    {
        yield 'thiếu mã địa phương' => ['A-12345'];
        yield 'ký tự ngoài phạm vi' => ['59A@12345'];
        yield 'biển ngoại giao ngoài baseline' => ['80-NG-123-45'];
        yield 'quá ít số cuối' => ['59A-123'];
        yield 'quá nhiều chữ cái' => ['59ABC-12345'];
    }

    public function testBackendValidatorChecksLengthsAndTypeId(): void
    {
        $plates = new LicensePlateService();
        $validator = new VehicleValidator($plates);
        $errors = $validator->validate('0', '59A@12345', str_repeat('A', 101), '', str_repeat('B', 1001));

        self::assertArrayHasKey('vehicle_type_id', $errors);
        self::assertArrayHasKey('display_plate', $errors);
        self::assertArrayHasKey('brand', $errors);
        self::assertArrayHasKey('notes', $errors);
    }
}
