<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Services\ResearchCsvExporter;
use App\Services\SyntheticResearchDataGenerator;
use PHPUnit\Framework\TestCase;

final class ResearchDataTest extends TestCase
{
    public function testSyntheticDatasetIsDeterministicAndCoversFourVehicleTypes(): void
    {
        $generator = new SyntheticResearchDataGenerator();
        $first = iterator_to_array($generator->generate(2000, 1407));
        $second = iterator_to_array($generator->generate(2000, 1407));

        self::assertCount(2000, $first);
        self::assertSame($first, $second);
        self::assertSame(
            ['motorbike', 'car', 'truck', 'bus'],
            array_values(array_unique(array_column($first, 'vehicle_type_code')))
        );
        self::assertSame(['synthetic'], array_values(array_unique(array_column($first, 'data_source'))));
        self::assertNotSame(
            $first[0]['event_key'],
            iterator_to_array($generator->generate(1, 1408))[0]['event_key']
        );
    }

    public function testCsvSchemaUsesPrivacyAllowlistAndWritesUtf8Rows(): void
    {
        $sensitiveColumns = ['full_name', 'phone', 'email', 'password_hash', 'plate', 'ip'];
        self::assertSame([], array_intersect($sensitiveColumns, ResearchCsvExporter::COLUMNS));

        $path = tempnam(sys_get_temp_dir(), 'autowash-research-');
        self::assertIsString($path);

        try {
            $rows = (new SyntheticResearchDataGenerator())->generate(2, 7);
            self::assertSame(2, (new ResearchCsvExporter())->write($path, $rows));
            $content = file_get_contents($path);
            self::assertIsString($content);
            self::assertStringStartsWith('schema_version,event_key,anonymous_user_key', $content);
            self::assertStringContainsString('synthetic', $content);
        } finally {
            @unlink($path);
        }
    }
}
