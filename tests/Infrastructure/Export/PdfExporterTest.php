<?php

namespace Tests\Infrastructure\Export;

use App\Infrastructure\Export\PdfExporter;
use PHPUnit\Framework\TestCase;

class PdfExporterTest extends TestCase
{
    public function testGenerateReturnsValidPdfString() : void
    {
        $exporter = new PdfExporter();
        $exporter->loadHtml('<h1>Hello World</h1>');
        $exporter->render();

        $pdf = $exporter->output();

        $this->assertNotEmpty($pdf);
        // PDF files start with %PDF-
        $this->assertStringStartsWith('%PDF-', $pdf);
    }
}
