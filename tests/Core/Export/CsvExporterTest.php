<?php

namespace Tests\Core\Export;

use App\Core\Export\CsvExporter;
use PHPUnit\Framework\TestCase;

class CsvExporterTest extends TestCase
{
    public function testGenerateReturnsValidCsv() : void
    {
        $headers = ['Name', 'Age'];
        $data = [
            ['John', 30],
            ['Jane', 25]
        ];

        $exporter = new CsvExporter($headers, $data);
        $csv = $exporter->generate();

        $this->assertStringContainsString('Name,Age', $csv);
        $this->assertStringContainsString('John,30', $csv);
        $this->assertStringContainsString('Jane,25', $csv);
    }
}
