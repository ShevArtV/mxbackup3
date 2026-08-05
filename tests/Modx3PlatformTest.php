<?php

namespace {
    if (!defined('MODX_CORE_PATH')) {
        define('MODX_CORE_PATH', '/tmp/modx-core/');
    }
}

namespace MODX\Revolution {
    if (!class_exists('MODX\\Revolution\\modX')) {
        /**
         * Минимальный двойник ядра тройки: логирование платформы проверяется
         * без живого MODX. Контейнер сервисов — как в xPDO 3 ($modx->services).
         */
        class modX
        {
            const LOG_LEVEL_DEBUG = 1;
            const LOG_LEVEL_INFO = 2;
            const LOG_LEVEL_WARN = 3;
            const LOG_LEVEL_ERROR = 4;

            public $mxl;
            public $services;
            public $packages = ['MxBackup\\Model' => true];
            public $logs = [];

            public function __construct()
            {
                $this->services = new class {
                    public $items = [];
                    public function has($name) { return isset($this->items[$name]); }
                    public function get($name) { return $this->items[$name] ?? null; }
                    public function add($name, $service) { $this->items[$name] = $service; }
                };
            }

            public function getOption($key, $options = null, $default = null) { return $default; }
            public function addPackage($package, $path, $prefix = null, $namespacePrefix = null) { return true; }
            public function log($level, $message) { $this->logs[] = compact('level', 'message'); }
        }
    }
}

namespace MxBackup\Tests {
    use MODX\Revolution\modX;
    use MxBackup\Platform\Modx3\Modx3Platform;
    use PHPUnit\Framework\TestCase;

    final class Modx3PlatformTest extends TestCase
    {
        public function testUsesMxLoggerFacadeAsPrimaryBackend()
        {
            $modx = new modX();
            $modx->mxl = new class {
                public $calls = [];
                public function log($tags, $level, $message, array $context, array $options)
                {
                    $this->calls[] = compact('tags', 'level', 'message', 'context', 'options');
                }
            };

            (new Modx3Platform($modx))->log('error', 'Backup failed', ['run_id' => 42]);

            self::assertCount(1, $modx->mxl->calls);
            self::assertSame(['mxbackup', 'backup'], $modx->mxl->calls[0]['tags']);
            self::assertSame('mxbackup_42', $modx->mxl->calls[0]['options']['process_uid']);
            self::assertSame([Modx3Platform::class], $modx->mxl->calls[0]['options']['skip_classes']);
            self::assertSame([], $modx->logs);
        }

        public function testFallsBackToServiceContainerWhenFacadeIsAbsent()
        {
            $modx = new modX();
            $logger = new class {
                public $calls = 0;
                public function log($tags, $level, $message, array $context, array $options) { $this->calls++; }
            };
            $modx->services->add('mxlogger', $logger);

            (new Modx3Platform($modx))->log('info', 'Backup started');

            self::assertSame(1, $logger->calls);
            self::assertSame([], $modx->logs);
        }

        public function testFallsBackToStandardModxLogWhenMxLoggerIsUnavailable()
        {
            $modx = new modX();

            (new Modx3Platform($modx))->log('warning', 'Fallback event', ['profile' => 'prod']);

            self::assertCount(1, $modx->logs);
            self::assertSame(modX::LOG_LEVEL_WARN, $modx->logs[0]['level']);
            self::assertStringContainsString('[mxbackup] Fallback event', $modx->logs[0]['message']);
            self::assertStringContainsString('"profile":"prod"', $modx->logs[0]['message']);
        }

        public function testFallsBackWhenMxLoggerThrows()
        {
            $modx = new modX();
            $modx->mxl = new class {
                public function log($tags, $level, $message, array $context, array $options)
                {
                    throw new \RuntimeException('logger storage failed');
                }
            };

            (new Modx3Platform($modx))->log('error', 'Original event');

            self::assertCount(1, $modx->logs);
            self::assertSame(modX::LOG_LEVEL_ERROR, $modx->logs[0]['level']);
            self::assertStringContainsString('Original event', $modx->logs[0]['message']);
        }
    }
}
