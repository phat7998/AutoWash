<?php

declare(strict_types=1);

namespace Tests\Security;

use PHPUnit\Framework\TestCase;

final class HttpSecurityConfigurationTest extends TestCase
{
    public function testApacheRoutesThroughFrontControllerAndDeniesSensitiveFiles(): void
    {
        $projectRoot = dirname(__DIR__, 2);
        $htaccess = (string) file_get_contents($projectRoot . '/public/.htaccess');
        $dockerfile = (string) file_get_contents($projectRoot . '/docker/php/Dockerfile');

        self::assertStringContainsString('RewriteRule ^ index.php [QSA,L]', $htaccess);
        self::assertStringContainsString('Require all denied', $htaccess);
        self::assertStringContainsString('Options -Indexes', $htaccess);
        self::assertStringContainsString('a2enmod headers rewrite', $dockerfile);
        self::assertStringContainsString('AllowOverride All', $dockerfile);
        self::assertStringContainsString('expose_php=Off', $dockerfile);
        self::assertStringContainsString('ServerTokens Prod', $dockerfile);
    }

    public function testEnvironmentFileAndRuntimeLogsRemainIgnored(): void
    {
        $projectRoot = dirname(__DIR__, 2);
        $gitignore = (string) file_get_contents($projectRoot . '/.gitignore');
        $dockerignore = (string) file_get_contents($projectRoot . '/.dockerignore');

        self::assertStringContainsString('/.env', $gitignore);
        self::assertStringContainsString('/storage/logs/*', $gitignore);
        self::assertStringContainsString('.env', $dockerignore);
        self::assertStringContainsString('storage/logs/*', $dockerignore);
    }
}
