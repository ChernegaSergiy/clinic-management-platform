<?php

namespace App\Core;

use chillerlan\QRCode\QRCode;
use chillerlan\QRCode\QROptions;

class QrCodeGenerator
{
    public function generateQrCodeAsBase64(string $data): string
    {
        $options = new QROptions([
            'outputType' => QRCode::OUTPUT_IMAGE_PNG,
            'eccLevel'   => QRCode::ECC_L_DEFAULT,
            'scale'      => 5,
            'imageBase64' => true,
        ]);

        return (new QRCode($options))->render($data);
    }
}