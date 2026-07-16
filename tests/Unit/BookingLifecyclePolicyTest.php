<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Exceptions\CancellationCutoffException;
use App\Exceptions\InvalidBookingTransitionException;
use App\Services\BookingLifecyclePolicy;
use DateTimeImmutable;
use DateTimeZone;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class BookingLifecyclePolicyTest extends TestCase
{
    /** @return iterable<string, array{string, string}> */
    public static function validTransitions(): iterable
    {
        yield 'pending sang confirmed' => ['pending', 'confirmed'];
        yield 'pending sang cancelled' => ['pending', 'cancelled'];
        yield 'confirmed sang completed' => ['confirmed', 'completed'];
        yield 'confirmed sang cancelled' => ['confirmed', 'cancelled'];
        yield 'confirmed sang no_show' => ['confirmed', 'no_show'];
    }

    /** @return iterable<string, array{string, string}> */
    public static function invalidTransitions(): iterable
    {
        yield 'pending không hoàn thành trực tiếp' => ['pending', 'completed'];
        yield 'pending không no_show trực tiếp' => ['pending', 'no_show'];
        yield 'confirmed không xác nhận lại' => ['confirmed', 'confirmed'];
        yield 'completed là trạng thái cuối' => ['completed', 'cancelled'];
        yield 'cancelled là trạng thái cuối' => ['cancelled', 'confirmed'];
        yield 'no_show là trạng thái cuối' => ['no_show', 'confirmed'];
    }

    #[DataProvider('validTransitions')]
    public function testAcceptsEveryDeclaredTransition(string $from, string $to): void
    {
        (new BookingLifecyclePolicy())->assertTransition($from, $to);

        self::assertTrue(true);
    }

    #[DataProvider('invalidTransitions')]
    public function testRejectsTransitionsOutsideMatrix(string $from, string $to): void
    {
        $this->expectException(InvalidBookingTransitionException::class);

        (new BookingLifecyclePolicy())->assertTransition($from, $to);
    }

    public function testCustomerCanCancelAtExactTwoHourBoundary(): void
    {
        $timezone = new DateTimeZone('Asia/Ho_Chi_Minh');
        $now = new DateTimeImmutable('2026-07-16 10:00:00', $timezone);
        $start = new DateTimeImmutable('2026-07-16 12:00:00', $timezone);

        (new BookingLifecyclePolicy())->assertCustomerCancellation($start, $now);

        self::assertTrue(true);
    }

    public function testCustomerCannotCancelOneSecondInsideCutoff(): void
    {
        $timezone = new DateTimeZone('Asia/Ho_Chi_Minh');
        $now = new DateTimeImmutable('2026-07-16 10:00:01', $timezone);
        $start = new DateTimeImmutable('2026-07-16 12:00:00', $timezone);
        $this->expectException(CancellationCutoffException::class);

        (new BookingLifecyclePolicy())->assertCustomerCancellation($start, $now);
    }
}
