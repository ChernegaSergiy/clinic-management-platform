<?php

namespace Tests\Core\Export;

use App\Core\Export\ExcelExporter;
use PHPUnit\Framework\TestCase;

class ExcelExporterTest extends TestCase
{
    public function testGenerateReturnsValidExcelString() : void
    {
        $exporter = new ExcelExporter();
        $headers = ['Name', 'Age'];
        $data = [
            ['John', 30]
        ];

        $excel = $exporter->generate($headers, $data);

        // Check that the returned string is not empty and has something
        $this->assertNotEmpty($excel);
        // Excel files start with PK (zip format)
        $this->assertStringStartsWith("PK\x03\x04", $excel);
    }
}
