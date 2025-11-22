<?php

namespace App\Core;

use Dompdf\Dompdf;
use Dompdf\Options;

class PdfExporter
{
    private Dompdf $dompdf;

    public function __construct()
    {
        $options = new Options();
        $options->set('isHtml5ParserEnabled', true);
        $options->set('isRemoteEnabled', true);
        $this->dompdf = new Dompdf($options);
    }

    public function loadHtml(string $html): void
    {
        $this->dompdf->loadHtml($html);
    }

    public function render(): void
    {
        $this->dompdf->render();
    }

    public function stream(string $filename = 'document.pdf'): void
    {
        $this->dompdf->stream($filename);
    }

    public function download(string $filename = 'document.pdf'): void
    {
        $this->dompdf->stream($filename, ['Attachment' => true]);
    }
}
