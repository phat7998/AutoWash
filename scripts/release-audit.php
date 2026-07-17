<?php

declare(strict_types=1);

// phpcs:disable PSR1.Files.SideEffects -- CLI này vừa điều phối vừa khai báo helper nội bộ.

$projectRoot = dirname(__DIR__);
$failures = [];
$requiredDocuments = [
    'docs/DEMO_SCRIPT.md',
    'docs/DEFENSE_QA.md',
    'docs/KNOWN_LIMITATIONS.md',
    'docs/PERFORMANCE_REPORT.md',
    'docs/RELEASE_CHECKLIST.md',
];

foreach ($requiredDocuments as $document) {
    if (!is_file($projectRoot . '/' . $document)) {
        $failures[] = 'Thiếu tài liệu release: ' . $document;
    }
}

$traceability = (string) file_get_contents($projectRoot . '/docs/REQUIREMENT_TRACEABILITY.md');

foreach (preg_split('/\R/', $traceability) ?: [] as $lineNumber => $line) {
    if (str_contains($line, '| MUST |') && !str_ends_with(trim($line), '| Done |')) {
        $failures[] = sprintf(
            'Requirement MUST chưa Done tại REQUIREMENT_TRACEABILITY.md:%d',
            $lineNumber + 1
        );
    }
}

$qaFile = $projectRoot . '/docs/DEFENSE_QA.md';

if (is_file($qaFile)) {
    preg_match_all('/^###\s+Q\d{2}\./m', (string) file_get_contents($qaFile), $questions);

    if (count($questions[0]) !== 30) {
        $failures[] = 'DEFENSE_QA.md phải có đúng 30 câu hỏi đánh số Q01..Q30.';
    }
}

$sourceDirectories = ['app', 'bootstrap', 'config', 'database', 'public', 'resources', 'routes', 'scripts'];
$debugPattern = '/\b(?:var_dump|print_r|dd|dump)\s*\(|\b(?:TODO|FIXME|HACK)\b/i';
$superglobalPattern = '/\$_(?:GET|POST|REQUEST|FILES|COOKIE)\b/';
$sqlAccessPattern = '/->\s*(?:prepare|query|exec)\s*\(/';

foreach (phpFiles($projectRoot, $sourceDirectories) as $file) {
    $relative = substr($file, strlen($projectRoot) + 1);
    $contents = (string) file_get_contents($file);

    if ($relative !== 'scripts/release-audit.php' && preg_match($debugPattern, $contents) === 1) {
        $failures[] = 'Phát hiện debug marker hoặc TODO trong ' . $relative;
    }

    if ($relative !== 'app/Core/Request.php' && preg_match($superglobalPattern, $contents) === 1) {
        $failures[] = 'Truy cập superglobal ngoài Request trong ' . $relative;
    }

    if (
        (str_starts_with($relative, 'app/Controllers/') || str_starts_with($relative, 'resources/views/'))
        && preg_match($sqlAccessPattern, $contents) === 1
    ) {
        $failures[] = 'Phát hiện SQL ngoài Repository/Database trong ' . $relative;
    }
}

$trackedFiles = [];
$gitBinary = trim((string) shell_exec('command -v git 2>/dev/null'));
$gitStatus = 0;

if ($gitBinary !== '') {
    exec('git -C ' . escapeshellarg($projectRoot) . ' ls-files', $trackedFiles, $gitStatus);
}

if ($gitBinary === '' || $gitStatus !== 0) {
    $trackedFiles = firstPartyFiles($projectRoot);
}

foreach ($trackedFiles as $trackedFile) {
    if ($gitBinary !== '') {
        if ($trackedFile === '.env' || (str_starts_with($trackedFile, '.env.') && $trackedFile !== '.env.example')) {
            $failures[] = 'File môi trường không được phép commit: ' . $trackedFile;
        }
    }

    $path = $projectRoot . '/' . $trackedFile;

    if (!is_file($path) || filesize($path) > 2_000_000) {
        continue;
    }

    $contents = (string) file_get_contents($path);

    if (
        preg_match('/-----BEGIN (?:RSA |EC |OPENSSH )?PRIVATE KEY-----/', $contents) === 1
        || preg_match('/\bAKIA[0-9A-Z]{16}\b/', $contents) === 1
    ) {
        $failures[] = 'Phát hiện mẫu secret trong ' . $trackedFile;
    }
}

if ($failures !== []) {
    fwrite(STDERR, "Release audit thất bại:\n- " . implode("\n- ", array_unique($failures)) . PHP_EOL);
    exit(1);
}

echo "Release audit đạt: MUST traceability, tài liệu, layer, superglobal, debug và secret scan.\n";

/** @param list<string> $directories @return list<string> */
function phpFiles(string $root, array $directories): array
{
    $files = [];

    foreach ($directories as $directory) {
        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($root . '/' . $directory, FilesystemIterator::SKIP_DOTS)
        );

        foreach ($iterator as $file) {
            if ($file->isFile() && $file->getExtension() === 'php') {
                $files[] = $file->getPathname();
            }
        }
    }

    sort($files);

    return $files;
}

/** @return list<string> */
function firstPartyFiles(string $root): array
{
    $directories = [
        'app', 'bootstrap', 'config', 'database', 'docker', 'docs',
        'public', 'resources', 'routes', 'scripts', 'tests',
    ];
    $files = ['.env.example', 'composer.json', 'docker-compose.yml', 'README.md'];

    foreach ($directories as $directory) {
        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($root . '/' . $directory, FilesystemIterator::SKIP_DOTS)
        );

        foreach ($iterator as $file) {
            if ($file->isFile()) {
                $files[] = substr($file->getPathname(), strlen($root) + 1);
            }
        }
    }

    sort($files);

    return array_values(array_unique($files));
}
