<?php

namespace MxBackup\Tests;

use MxBackup\Processors\Mgr\Profile\Row;
use PHPUnit\Framework\TestCase;

final class ArchitectureTest extends TestCase
{
    public function testCoreDoesNotDependOnModxClasses()
    {
        $root = dirname(__DIR__) . '/core/components/mxbackup/src/Core';
        $iterator = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($root, \FilesystemIterator::SKIP_DOTS));
        foreach ($iterator as $file) {
            if ($file->getExtension() !== 'php') continue;
            $source = file_get_contents($file->getPathname());
            self::assertDoesNotMatchRegularExpression('/\\\\?(?:modX|xPDO|modProcessor|modMail)\\b/', $source, $file->getPathname());
            self::assertStringNotContainsString('Platform\\Modx3', $source, $file->getPathname());
            self::assertStringNotContainsString('MODX\\Revolution', $source, $file->getPathname());
        }
    }

    public function testManagerSettingsUsePostSafeFieldNames()
    {
        $root = dirname(__DIR__);
        $page = file_get_contents($root . '/assets/components/mxbackup/js/mgr/pages/SettingsPage.vue');
        $processor = file_get_contents($root . '/core/components/mxbackup/src/Processors/Mgr/Config/Get.php');

        // PHP превращает точки в именах POST-полей в подчёркивания, поэтому
        // форма работает с безопасными именами, а маппинг живёт в процессоре.
        self::assertDoesNotMatchRegularExpression('/v-model="form\\.mxbackup\\./', $page);
        self::assertStringContainsString("'storage_path' => 'mxbackup.storage_path'", $processor);
        self::assertStringContainsString(
            'array_key_exists($field, $properties)',
            file_get_contents($root . '/core/components/mxbackup/src/Processors/Mgr/Config/Update.php')
        );
    }

    public function testOnlyRunHistoryHasAnXpdoModel()
    {
        $schema = file_get_contents(dirname(__DIR__) . '/core/components/mxbackup/schema/mxbackup.mysql.schema.xml');

        self::assertStringContainsString('class="MxBackupRun"', $schema);
        self::assertStringNotContainsString('class="MxBackupProfile"', $schema);
        self::assertStringNotContainsString('class="MxBackupRule"', $schema);
    }

    public function testManagerProfileRowsNeverExposeEncryptionPassword()
    {
        $row = Row::make([
            'name' => 'prod',
            'mode' => 'prod',
            'active' => true,
            'format' => 'zip',
            'encryption' => ['enabled' => true, 'password' => 'must-not-leak'],
            'files' => ['include' => ['*'], 'exclude' => []],
            'masking' => ['standard' => false],
        ]);

        self::assertTrue($row['encryption_enabled']);
        self::assertTrue($row['encryption_password_set']);
        self::assertArrayNotHasKey('encryption_password', $row);
        self::assertStringNotContainsString('must-not-leak', json_encode($row));
    }

    /**
     * В базовом `MODX\Revolution\Processors\Processor` свойство `$permission`
     * объявлено публичным. Сужение видимости в наследнике — фатал ещё до
     * выполнения: коннектор отдаёт HTML вместо JSON, а грид молча пустеет.
     */
    public function testProcessorsDoNotNarrowPermissionVisibility()
    {
        $root = dirname(__DIR__) . '/core/components/mxbackup/src/Processors';
        $iterator = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($root, \FilesystemIterator::SKIP_DOTS));
        $checked = 0;

        foreach ($iterator as $file) {
            if ($file->getExtension() !== 'php') continue;
            $source = file_get_contents($file->getPathname());
            $checked++;
            self::assertDoesNotMatchRegularExpression(
                '/(?:protected|private)\s+\$permission\b/',
                $source,
                $file->getPathname()
            );
        }

        self::assertGreaterThan(0, $checked);
    }

    /**
     * Конвенция линии MODX 3: общий стек берётся из Import Map пакета VueTools,
     * свой Vue/PrimeVue в бандл не попадает.
     */
    public function testManagerBundleKeepsSharedStackExternal()
    {
        $bundle = dirname(__DIR__) . '/assets/components/mxbackup/js/mgr/vue-dist/mxbackup.min.js';
        if (!is_file($bundle)) {
            self::markTestSkipped('Бандл не собран: npm run build в assets/components/mxbackup.');
        }

        $source = file_get_contents($bundle);
        preg_match_all('/from"([^"]+)"/', $source, $matches);
        $imports = array_values(array_unique($matches[1]));
        sort($imports);

        self::assertSame(['@vuetools/useApi', '@vuetools/useLexicon', 'primevue', 'vue'], $imports);
        // Признак самостоятельно вшитого Vue: рантайм-функции ядра в бандле.
        self::assertStringNotContainsString('__vue_app__', $source);
    }

    /**
     * Строки интерфейса живут в лексиконе; в компонентах — только ключи.
     */
    public function testManagerComponentsUseLexiconKeysOnly()
    {
        $root = dirname(__DIR__) . '/assets/components/mxbackup/js/mgr';
        $iterator = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($root, \FilesystemIterator::SKIP_DOTS));
        $checked = 0;

        foreach ($iterator as $file) {
            if ($file->getExtension() !== 'vue') continue;
            $source = file_get_contents($file->getPathname());
            $checked++;
            // Подпись, заданная литералом вместо t('ключ'). Lookbehind
            // отсекает биндинги (:header="…"), где значение — выражение.
            self::assertDoesNotMatchRegularExpression(
                '/(?<![:\\w-])(?:label|header|placeholder|summary)="[^"{}]*[A-Za-zА-Яа-я]{2,}[^"{}]*"/u',
                $source,
                $file->getPathname()
            );
        }

        self::assertGreaterThan(0, $checked);
    }
}
