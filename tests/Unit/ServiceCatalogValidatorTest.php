<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Validation\ServiceCatalogValidator;
use PHPUnit\Framework\TestCase;

final class ServiceCatalogValidatorTest extends TestCase
{
    public function testValidatesSupportedPriceAndNormalizesMoneyWithoutFloat(): void
    {
        $validator = new ServiceCatalogValidator();
        $types = [['id' => 1, 'code' => 'motorbike', 'display_name' => 'Xe máy']];
        $valid = ['1' => [
            'is_supported' => '1',
            'price' => '00040.5',
            'duration_minutes' => '20',
            'capacity_units_override' => '',
        ]];

        self::assertSame([], $validator->validate('WASH_01', 'Rửa xe', '', $valid, $types));
        self::assertSame('40.50', $validator->normalizeMoney('00040.5'));
        self::assertSame('0.50', $validator->normalizeMoney('0.5'));

        $invalid = $valid;
        $invalid['1']['price'] = '0';
        $invalid['1']['duration_minutes'] = '0';
        $invalid['1']['capacity_units_override'] = '-1';
        $errors = $validator->validate('sai ma', '', str_repeat('x', 2001), $invalid, $types);

        self::assertArrayHasKey('code', $errors);
        self::assertArrayHasKey('name', $errors);
        self::assertArrayHasKey('description', $errors);
        self::assertArrayHasKey('prices.1.price', $errors);
        self::assertArrayHasKey('prices.1.duration_minutes', $errors);
        self::assertArrayHasKey('prices.1.capacity_units_override', $errors);
    }

    public function testUnsupportedConfigurationDoesNotRequirePriceOrDuration(): void
    {
        $validator = new ServiceCatalogValidator();
        $types = [['id' => 1, 'code' => 'motorbike', 'display_name' => 'Xe máy']];

        self::assertSame([], $validator->validate(
            'NO_ENGINE',
            'Không hỗ trợ khoang máy',
            '',
            ['1' => ['is_supported' => '', 'price' => '', 'duration_minutes' => '']],
            $types
        ));
    }
}
