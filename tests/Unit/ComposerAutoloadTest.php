<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Support\Env;
use PHPUnit\Framework\TestCase;

final class ComposerAutoloadTest extends TestCase
{
    public function testComposerAutoloadsApplicationClass(): void
    {
        self::assertTrue(class_exists(Env::class));
        self::assertSame('fallback', Env::string('AUTOWASH_SMOKE_MISSING_KEY', 'fallback'));
    }
}
