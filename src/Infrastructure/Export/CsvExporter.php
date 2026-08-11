<?php

/*
 *
 *                      _
 *   _ __ ___   ___  __| | ___ ___  _ __ ___       _   _  __ _
 *  | '_ ` _ \ / _ \/ _` |/ __/ _ \| '__/ _ \_____| | | |/ _` |
 *  | | | | | |  __/ (_| | (_| (_) | | |  __/_____| |_| | (_| |
 *  |_| |_| |_|\___|\__,_|\___\___/|_|  \___|      \__,_|\__,_|
 *
 * This program is free software: you can redistribute and/or modify
 * it under the terms of the CSSM Unlimited License v2.0.
 *
 * This license permits unlimited use, modification, and distribution
 * for any purpose while maintaining authorship attribution.
 *
 * The software is provided "as is" without warranty of any kind.
 *
 * @author MedCore Ukraine
 * @link https://medcore.pp.ua/
 *
 *
 */

namespace App\Infrastructure\Export;

class CsvExporter
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

    public function generate() : string
    {
        $output = fopen('php://temp', 'r+');
        if (false === $output) {
            return '';
        }

        fputcsv($output, $this->headers, $this->delimiter, $this->enclosure);

        foreach ($this->data as $row) {
            fputcsv($output, $row, $this->delimiter, $this->enclosure);
        }

        rewind($output);
        $csv = stream_get_contents($output);
        fclose($output);

        return $csv;
    }
}
