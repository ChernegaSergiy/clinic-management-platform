<?php

namespace App\Core;

class CSVExporter
{
    private array $headers;
    private array $data;
    private string $delimiter;
    private string $enclosure;

    public function __construct(array $headers, array $data, string $delimiter = ',', string $enclosure = '"')
    {
        $this->headers = $headers;
        $this->data = $data;
        $this->delimiter = $delimiter;
        $this->enclosure = $enclosure;
    }

    public function generate(): string
    {
        $output = fopen('php://temp', 'r+');
        if ($output === false) {
            // Handle error, e.g., throw an exception
            return '';
        }

        // Write headers
        fputcsv($output, $this->headers, $this->delimiter, $this->enclosure);

        // Write data
        foreach ($this->data as $row) {
            fputcsv($output, $row, $this->delimiter, $this->enclosure);
        }

        rewind($output);
        $csv = stream_get_contents($output);
        fclose($output);

        return $csv;
    }

    public function download(string $filename = 'export.csv'): void
    {
        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Pragma: no-cache');
        header('Expires: 0');

        echo $this->generate();
        exit();
    }
}