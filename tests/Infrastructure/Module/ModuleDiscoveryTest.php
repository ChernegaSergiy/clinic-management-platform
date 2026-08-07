<?php

namespace Tests\Infrastructure\Module;

use App\Infrastructure\Module\ModuleDiscovery;
use PHPUnit\Framework\TestCase;

class ModuleDiscoveryTest extends TestCase
{
    private string $tempDir;

    protected function setUp() : void
    {
        $this->tempDir = sys_get_temp_dir() . '/module_discovery_test_' . uniqid();
        mkdir($this->tempDir, 0777, true);
    }

    protected function tearDown() : void
    {
        if (is_dir($this->tempDir)) {
            $this->deleteDir($this->tempDir);
        }
    }

    private function deleteDir(string $dir) : void
    {
        $items = scandir($dir);
        foreach ($items as $item) {
            if ('.' === $item || '..' === $item) {
                continue;
            }
            $path = $dir . DIRECTORY_SEPARATOR . $item;
            if (is_dir($path)) {
                $this->deleteDir($path);
            } else {
                unlink($path);
            }
        }
        rmdir($dir);
    }

    public function testDiscoversValidModules() : void
    {
        mkdir($this->tempDir . '/User');
        mkdir($this->tempDir . '/Billing');
        $discovery = new ModuleDiscovery($this->tempDir);
        $modules = $discovery->discover();
        sort($modules);
        $this->assertSame(['Billing', 'User'], $modules);
    }

    public function testIgnoresInvalidDirectories() : void
    {
        mkdir($this->tempDir . '/ValidModule');
        touch($this->tempDir . '/not_a_dir.txt');
        mkdir($this->tempDir . '/.hidden');
        $discovery = new ModuleDiscovery($this->tempDir);
        $modules = $discovery->discover();
        $this->assertSame(['ValidModule'], $modules);
    }
}
