<?php

namespace Tests\Core\Export;

use App\Core\Export\JsonExporter;
use PHPUnit\Framework\TestCase;

class JsonExporterTest extends TestCase
{
    public function testGenerateReturnsValidJson() : void
    {
        $exporter = new JsonExporter();
        $data = ['name' => 'John', 'age' => 30];

        $json = $exporter->generate($data);

        $this->assertJson($json);
        $this->assertSame(json_encode($data, JSON_PRETTY_PRINT), $json);
    }
}
