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

namespace App\Core\Export;

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
        $options->set('defaultFont', 'dejavusans');
        $this->dompdf = new Dompdf($options);
        $this->dompdf->setPaper('A4', 'portrait');
    }

    public function loadHtml(string $html) : void
    {
        $this->dompdf->loadHtml($html);
    }

    public function render() : void
    {
        $this->dompdf->render();
    }

    public function output() : string
    {
        return $this->dompdf->output();
    }
}
