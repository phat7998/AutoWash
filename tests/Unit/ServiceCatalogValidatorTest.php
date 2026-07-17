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
        $groups = [['id' => 2, 'code' => 'ADD_ON', 'name' => 'Dịch vụ bổ sung']];
        $valid = ['1' => [
            'is_supported' => '1',
            'price' => '00040.5',
            'duration_minutes' => '20',
            'capacity_units_override' => '',
        ]];

        self::assertSame([], $validator->validate('WASH_01', 'Rửa xe', '', '2', $valid, $types, $groups));
        self::assertSame('40.50', $validator->normalizeMoney('00040.5'));
        self::assertSame('0.50', $validator->normalizeMoney('0.5'));

        $invalid = $valid;
        $invalid['1']['price'] = '0';
        $invalid['1']['duration_minutes'] = '0';
        $invalid['1']['capacity_units_override'] = '-1';
        $errors = $validator->validate('sai ma', '', str_repeat('x', 2001), '999', $invalid, $types, $groups);

        self::assertArrayHasKey('code', $errors);
        self::assertArrayHasKey('name', $errors);
        self::assertArrayHasKey('description', $errors);
        self::assertArrayHasKey('service_group_id', $errors);
        self::assertArrayHasKey('prices.1.price', $errors);
        self::assertArrayHasKey('prices.1.duration_minutes', $errors);
        self::assertArrayHasKey('prices.1.capacity_units_override', $errors);
    }

    public function testUnsupportedConfigurationDoesNotRequirePriceOrDuration(): void
    {
        $validator = new ServiceCatalogValidator();
        $types = [['id' => 1, 'code' => 'motorbike', 'display_name' => 'Xe máy']];
        $groups = [['id' => 2, 'code' => 'ADD_ON', 'name' => 'Dịch vụ bổ sung']];

        self::assertSame([], $validator->validate(
            'NO_ENGINE',
            'Không hỗ trợ khoang máy',
            '',
            '2',
            ['1' => ['is_supported' => '', 'price' => '', 'duration_minutes' => '']],
            $types,
            $groups
        ));
    }
}
