<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Core\Session;
use PHPUnit\Framework\Attributes\RunInSeparateProcess;
use PHPUnit\Framework\Attributes\PreserveGlobalState;
use PHPUnit\Framework\TestCase;

final class SessionTest extends TestCase
{
    public function testFlashLivesForExactlyOneFollowingRequest(): void
    {
        $data = [];
        $firstRequest = new Session($data);
        $firstRequest->flash('success', 'Đã lưu');

        $secondRequest = new Session($data);
        self::assertSame('Đã lưu', $secondRequest->get('success'));

        $thirdRequest = new Session($data);
        self::assertNull($thirdRequest->get('success'));
    }

    #[RunInSeparateProcess]
    #[PreserveGlobalState(false)]
    public function testCookieUsesRequiredSecurityFlags(): void
    {
        Session::start(['name' => 'AUTOWASH_TEST_SESSION'], true);
        $parameters = session_get_cookie_params();

        self::assertSame('AUTOWASH_TEST_SESSION', session_name());
        self::assertTrue($parameters['secure']);
        self::assertTrue($parameters['httponly']);
        self::assertSame('Lax', $parameters['samesite']);

        session_destroy();
    }
}
