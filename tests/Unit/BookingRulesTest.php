<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Exceptions\BookingWindowExceededException;
use App\Exceptions\ValidationException;
use App\Services\BookingWindowPolicy;
use App\Services\BookingResourceCalculator;
use App\Services\PriceCalculator;
use App\Validation\BookingValidator;
use DateTimeImmutable;
use DateTimeZone;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class BookingRulesTest extends TestCase
{
    /** @return iterable<string, array{int}> */
    public static function tierWindows(): iterable
    {
        yield 'Member' => [7];
        yield 'Silver' => [10];
        yield 'Gold' => [12];
        yield 'Platinum' => [14];
    }

    #[DataProvider('tierWindows')]
    public function testBookingWindowAcceptsExactTierBoundaryAndRejectsBeyond(int $windowDays): void
    {
        $timezone = new DateTimeZone('Asia/Ho_Chi_Minh');
        $policy = new BookingWindowPolicy($timezone);
        $now = new DateTimeImmutable('2026-07-16 23:30:00', $timezone);

        self::assertSame(
            $windowDays,
            $policy->assertAllowed(
                $now->modify('+' . $windowDays . ' days')->format('Y-m-d'),
                $windowDays,
                $now
            )
        );

        $this->expectException(BookingWindowExceededException::class);
        $policy->assertAllowed(
            $now->modify('+' . ($windowDays + 1) . ' days')->format('Y-m-d'),
            $windowDays,
            $now
        );
    }

    public function testBookingWindowRejectsPastDateAndAcceptsToday(): void
    {
        $timezone = new DateTimeZone('Asia/Ho_Chi_Minh');
        $policy = new BookingWindowPolicy($timezone);
        $now = new DateTimeImmutable('2026-07-16 01:00:00', $timezone);

        self::assertSame(0, $policy->assertAllowed('2026-07-16', 7, $now));

        $this->expectException(ValidationException::class);
        $policy->assertAllowed('2026-07-15', 7, $now);
    }

    public function testPriceCalculatorAddsDecimalStringsWithoutFloatAndKeepsDiscountsZero(): void
    {
        $price = (new PriceCalculator())->calculate([
            ['price' => '100000.00'],
            ['price' => '180000.50'],
            ['price' => '0.05'],
        ]);

        self::assertSame('280000.55', $price->subtotal);
        self::assertSame('0.00', $price->perkDiscount);
        self::assertSame('0.00', $price->promotionDiscount);
        self::assertSame('0.00', $price->rewardDiscount);
        self::assertSame('280000.55', $price->finalPrice);
    }

    public function testResourceCalculatorSumsDurationAndUsesHighestCapacityWithVehicleFloor(): void
    {
        $calculator = new BookingResourceCalculator();
        $resources = $calculator->calculate(2, [
            ['duration_minutes' => 40, 'capacity_units' => 2],
            ['duration_minutes' => 60, 'capacity_units' => 3],
        ]);

        self::assertSame(100, $resources->durationMinutes);
        self::assertSame(3, $resources->capacityUnits);

        $vehicleFloor = $calculator->calculate(5, [
            ['duration_minutes' => 20, 'capacity_units' => 1],
            ['duration_minutes' => 35, 'capacity_units' => 3],
        ]);
        self::assertSame(55, $vehicleFloor->durationMinutes);
        self::assertSame(5, $vehicleFloor->capacityUnits);
    }

    public function testPriceCalculatorChoosesBestBenefitsAndKeepsFinalNonNegative(): void
    {
        $price = (new PriceCalculator())->calculate(
            [
                ['service_id' => 1, 'price' => '100000.00'],
                ['service_id' => 2, 'price' => '50000.00'],
            ],
            [
                ['id' => 1, 'perk_type' => 'percentage_discount', 'value' => '5.00'],
                ['id' => 2, 'perk_type' => 'fixed_discount', 'value' => '10000.00'],
            ],
            [
                [
                    'id' => 10, 'discount_type' => 'percentage', 'discount_value' => '10.00',
                    'max_discount' => null, 'service_id' => null, 'end_at' => '2030-12-31 00:00:00',
                ],
                [
                    'id' => 11, 'discount_type' => 'fixed', 'discount_value' => '15000.00',
                    'max_discount' => null, 'service_id' => null, 'end_at' => '2030-06-30 00:00:00',
                ],
            ],
            [
                'redemption_id' => 20, 'reward_type' => 'percentage_discount',
                'value' => '50.00', 'max_discount' => '20000.00', 'service_id' => null,
            ]
        );

        self::assertSame('150000.00', $price->subtotal);
        self::assertSame('10000.00', $price->perkDiscount);
        self::assertSame('15000.00', $price->promotionDiscount);
        self::assertSame('20000.00', $price->rewardDiscount);
        self::assertSame('105000.00', $price->finalPrice);
        self::assertSame(2, $price->perkId);
        self::assertSame(11, $price->promotionId);
        self::assertSame(20, $price->rewardRedemptionId);

        $free = (new PriceCalculator())->calculate(
            [['service_id' => 1, 'price' => '40000.00']],
            [['id' => 3, 'perk_type' => 'fixed_discount', 'value' => '90000.00']],
            [],
            null
        );
        self::assertSame('0.00', $free->finalPrice);
        self::assertSame('40000.00', $free->perkDiscount);
    }

    public function testValidatorNormalizesIdsAndRejectsDuplicateServices(): void
    {
        $validator = new BookingValidator();
        $selection = $validator->selection('2', '8', ['7', '3']);

        self::assertSame(2, $selection->vehicleId);
        self::assertSame(8, $selection->startSlotId);
        self::assertSame([3, 7], $selection->serviceIds);

        $this->expectException(ValidationException::class);
        $validator->selection('2', '8', ['3', '3']);
    }

    public function testValidatorRejectsEmptySelectionWithPackageMessage(): void
    {
        try {
            (new BookingValidator())->selection('2', '8', []);
            self::fail('Booking không chọn service phải bị từ chối.');
        } catch (ValidationException $exception) {
            self::assertSame(
                'Vui lòng chọn một gói rửa chính.',
                $exception->errors()['service_ids']
            );
        }
    }
}
