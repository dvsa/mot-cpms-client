<?php

declare(strict_types=1);

namespace CpmsClientTest;

use DvsaLogger\Logger\MotLogger;
use Monolog\Handler\StreamHandler;
use Monolog\Level;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;

/**
 * @phpstan-type LoggerWriter array{
 *     type: string,
 *     path?: string,
 *     formatter?: string,
 *     level?: string,
 *     enabled?: bool
 * }
 * @phpstan-type LoggerDefinition array{
 *     channel: string,
 *     environment_levels?: array<string, string>,
 *     writers: list<LoggerWriter>
 * }
 * @phpstan-type LoggerConfig array{
 *     loggers?: array<string, LoggerDefinition>,
 *     writers?: list<LoggerWriter>,
 *     channel?: string
 * }
 */
class LoggerOutputTest extends TestCase
{
    /**
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public function testMotLoggerConfigurationIsLoaded(): void
    {
        $serviceManager = Bootstrap::getInstance()->getServiceManager();
        /** @var array{mot_logger: LoggerConfig} $config */
        $config = $serviceManager->get('config');

        $this->assertArrayHasKey(
            'mot_logger',
            $config,
            'mot_logger configuration must be present in merged config'
        );
    }

    /**
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public function testMotLoggerHasContent(): void
    {
        $serviceManager = Bootstrap::getInstance()->getServiceManager();
        /** @var array{mot_logger: LoggerConfig} $config */
        $config = $serviceManager->get('config');
        $motLoggerConfig = $config['mot_logger'];

        $hasLoggers = !empty($motLoggerConfig['loggers']);
        $hasLegacyFormat = (isset($motLoggerConfig['writers']) || isset($motLoggerConfig['channel']));

        $this->assertTrue(
            $hasLoggers || $hasLegacyFormat,
            'mot_logger must have either loggers (new) or writers/channel (legacy) configured'
        );
    }

    public function testCpmsApiClientLoggerConfiguredInProduction(): void
    {
        /** @var array{mot_logger: array{loggers: array<string, LoggerDefinition>}} $productionConfig */
        $productionConfig = require __DIR__ . '/../../config/autoload/cpms-client.global.php';
        $motLoggerConfig = $productionConfig['mot_logger'];

        $this->assertArrayHasKey(
            'loggers',
            $motLoggerConfig,
            'Production config must have loggers section (new format)'
        );

        $loggers = $motLoggerConfig['loggers'];
        $this->assertArrayHasKey(
            'cpms-api-client',
            $loggers,
            'cpms-api-client logger must be configured in production'
        );
    }

    public function testCpmsClientLoggerHasCorrectChannel(): void
    {
        /** @var array{mot_logger: array{loggers: array<string, LoggerDefinition>}} $productionConfig */
        $productionConfig = require __DIR__ . '/../../config/autoload/cpms-client.global.php';
        $cpmsLogger = $productionConfig['mot_logger']['loggers']['cpms-api-client'];

        $this->assertArrayHasKey(
            'channel',
            $cpmsLogger,
            'cpms-api-client logger must have channel defined'
        );

        $this->assertEquals(
            'cpms-api-client',
            $cpmsLogger['channel'],
            'cpms-api-client logger channel must be "cpms-api-client"'
        );
    }

    public function testCpmsClientLoggerHasWritersConfigured(): void
    {
        /** @var array{mot_logger: array{loggers: array<string, LoggerDefinition>}} $productionConfig */
        $productionConfig = require __DIR__ . '/../../config/autoload/cpms-client.global.php';
        $cpmsLogger = $productionConfig['mot_logger']['loggers']['cpms-api-client'];

        $this->assertArrayHasKey(
            'writers',
            $cpmsLogger,
            'cpms-api-client logger must have writers configured'
        );

        $writers = $cpmsLogger['writers'];
        $this->assertNotEmpty(
            $writers,
            'cpms-api-client logger must have at least one writer'
        );
    }

    public function testCpmsClientLoggerWritersHaveCorrectStructure(): void
    {
        /** @var array{mot_logger: array{loggers: array<string, LoggerDefinition>}} $productionConfig */
        $productionConfig = require __DIR__ . '/../../config/autoload/cpms-client.global.php';
        $writers = $productionConfig['mot_logger']['loggers']['cpms-api-client']['writers'];

        foreach ($writers as $idx => $writer) {
            $this->assertArrayHasKey(
                'type',
                $writer,
                "Writer [$idx] must have 'type' field"
            );

            $this->assertContains(
                $writer['type'],
                ['stream', 'database'],
                "Writer [$idx] type must be 'stream' or 'database', got: {$writer['type']}"
            );

            if ($writer['type'] === 'stream') {
                $this->assertArrayHasKey(
                    'path',
                    $writer,
                    "Stream writer [$idx] must have 'path' configured"
                );

                if (isset($writer['formatter'])) {
                    $this->assertContains(
                        $writer['formatter'],
                        ['pipe', 'json'],
                        "Stream writer [$idx] formatter must be 'pipe' or 'json'"
                    );
                }
            }
        }
    }

    /**
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public function testLoggerServiceCanBeRetrieved(): void
    {
        $serviceManager = Bootstrap::getInstance()->getServiceManager();

        $this->assertTrue(
            $serviceManager->has(MotLogger::class),
            'MotLogger service must be registered in container'
        );

        /** @var MotLogger $logger */
        $logger = $serviceManager->get(MotLogger::class);
        $this->assertInstanceOf(MotLogger::class, $logger);
    }

    /**
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public function testWritersWithPathCorrectlyLogsAndSavesOutput(): void
    {
        $tempFile = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'cpms-logger-' . uniqid() . '.log';

        $serviceManager = Bootstrap::getInstance()->getServiceManager();
        /** @var MotLogger $motLogger */
        $motLogger = $serviceManager->get(MotLogger::class);

        $monolog = $motLogger->getLogger();
        // push a StreamHandler that writes to a temp file to verify writing
        $stream = new StreamHandler($tempFile, Level::Debug);
        $monolog->pushHandler($stream);

        $unique = 'TEST_LOG_' . uniqid();
        $motLogger->info($unique, ['capture' => 'yes']);

        $stream->close();
        $monolog->popHandler();

        $this->assertFileExists($tempFile, "Expected log file at {$tempFile}");
        $content = file_get_contents($tempFile);
        $this->assertIsString($content);
        $this->assertStringContainsString($unique, $content, 'Logged message should be present in file');

        @unlink($tempFile);
    }
}
