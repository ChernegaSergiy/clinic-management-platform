<?php

namespace Tests\Infrastructure\Export {

    use App\Infrastructure\Export\CsvExporter;
    use PHPUnit\Framework\TestCase;

    class CsvExporterTest extends TestCase
    {
        public static bool $failFopen = false;

        protected function tearDown() : void
        {
            self::$failFopen = false;
            parent::tearDown();
        }

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

        public function testGenerateReturnsEmptyStringWhenFopenFails() : void
        {
            self::$failFopen = true;
            $exporter = new CsvExporter([], []);
            $this->assertSame('', $exporter->generate());
        }
    }

}

namespace App\Infrastructure\Export {
    function fopen($filename, $mode)
    {
        if (\Tests\Infrastructure\Export\CsvExporterTest::$failFopen ?? false) {
            return false;
        }
        return \fopen($filename, $mode);
    }
}
