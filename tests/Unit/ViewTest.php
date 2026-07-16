<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Core\View;
use App\Support\Html;
use PHPUnit\Framework\TestCase;

final class ViewTest extends TestCase
{
    public function testHtmlEscapeProtectsTextAndAttributes(): void
    {
        $payload = '<script>alert("xss")</script>';

        self::assertSame(
            '&lt;script&gt;alert(&quot;xss&quot;)&lt;/script&gt;',
            Html::escape($payload)
        );
        self::assertSame(Html::escape($payload), View::escape($payload));
    }

    public function testViewEscapesDynamicErrorContent(): void
    {
        $view = new View(dirname(__DIR__, 2) . '/resources/views');
        $html = $view->render('errors/404', [
            'title' => '<Không an toàn>',
            'message' => '<img src=x onerror=alert(1)>',
        ]);

        self::assertStringNotContainsString('<img src=x', $html);
        self::assertStringContainsString('&lt;img src=x onerror=alert(1)&gt;', $html);
    }
}
