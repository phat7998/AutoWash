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

    #[RunInSeparateProcess]
    #[PreserveGlobalState(false)]
    public function testRegenerateChangesIdAndInvalidateDestroysSession(): void
    {
        $session = Session::start(['name' => 'AUTOWASH_AUTH_TEST'], false);
        $session->put('auth_user', ['id' => 1]);
        $oldId = session_id();

        $session->regenerate();

        self::assertNotSame($oldId, session_id());
        self::assertSame(['id' => 1], $session->get('auth_user'));

        $session->invalidate();

        self::assertSame(PHP_SESSION_NONE, session_status());
        self::assertNull($session->get('auth_user'));
    }
}
