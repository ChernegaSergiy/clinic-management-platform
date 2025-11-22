<?php

namespace App\Core;

class JsonExporter
{
    public function export(array $data, string $filename = 'export.json'): void
    {
        header('Content-Type: application/json');
        header('Content-Disposition: attachment;filename="' . $filename . '"');
        echo json_encode($data, JSON_PRETTY_PRINT);
        exit;
    }
}
