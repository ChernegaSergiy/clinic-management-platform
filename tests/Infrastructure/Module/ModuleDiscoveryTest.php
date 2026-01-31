<?php

namespace Tests\Infrastructure\Module;

use App\Infrastructure\Module\ModuleDiscovery;
use PHPUnit\Framework\TestCase;

final class ModuleDiscoveryTest extends TestCase
{
    private string $tempDir;

    protected function setUp(): void
    {
        $this->tempDir = sys_get_temp_dir() . '/module_discovery_test_' . uniqid();
        mkdir($this->tempDir, 0777, true);
    }

    protected function tearDown(): void
    {
        $this->deleteDir($this->tempDir);
    }

    public function testDiscoversValidModules(): void
    {
        mkdir($this->tempDir . '/User');
        mkdir($this->tempDir . '/Billing');

        $discovery = new ModuleDiscovery($this->tempDir);
        $modules = $discovery->discover();

        $this->assertContains('User', $modules);
        $this->assertContains('Billing', $modules);
    }

    public function testIgnoresInvalidDirectories(): void
    {
        mkdir($this->tempDir . '/ValidModule');
        file_put_contents($this->tempDir . '/not_a_module.txt', 'test');

        $discovery = new ModuleDiscovery($this->tempDir);
        $modules = $discovery->discover();

        $this->assertContains('ValidModule', $modules);
        $this->assertNotContains('not_a_module.txt', $modules);
    }

    private function deleteDir(string $dir): void
    {
        if (!is_dir($dir)) {
            return;
        }

        foreach (scandir($dir) as $item) {
            if ($item === '.' || $item === '..') {
                continue;
            }

            $path = $dir . '/' . $item;
            is_dir($path) ? $this->deleteDir($path) : unlink($path);
        }

        rmdir($dir);
    }
}
