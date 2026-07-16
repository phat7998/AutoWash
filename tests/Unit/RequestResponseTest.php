<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Core\Request;
use App\Core\Response;
use PHPUnit\Framework\TestCase;

final class RequestResponseTest extends TestCase
{
    public function testRequestNormalizesPathAndReadsInputAndHeader(): void
    {
        $request = new Request(
            'post',
            '/dich-vu/42/?trang=2',
            ['trang' => '2'],
            ['ten' => 'Rửa tiêu chuẩn'],
            ['HTTPS' => 'on'],
            ['x-request-id' => 'request-1234']
        );

        self::assertSame('POST', $request->method());
        self::assertSame('/dich-vu/42', $request->path());
        self::assertSame('Rửa tiêu chuẩn', $request->input('ten'));
        self::assertSame('2', $request->input('trang'));
        self::assertSame('request-1234', $request->header('X-Request-ID'));
        self::assertTrue($request->isSecure());
    }

    public function testResponseCreatesSafeJsonAndRedirect(): void
    {
        $json = Response::json(['status' => 'ổn']);
        $redirect = Response::redirect('/trang-chu');

        self::assertSame(200, $json->statusCode());
        self::assertSame('{"status":"ổn"}', $json->body());
        self::assertSame('application/json; charset=UTF-8', $json->headers()['Content-Type']);
        self::assertSame(303, $redirect->statusCode());
        self::assertSame('/trang-chu', $redirect->headers()['Location']);
    }
}
