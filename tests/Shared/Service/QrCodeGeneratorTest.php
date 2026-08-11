<?php

namespace App\Tests\Shared\Service;

use App\Shared\Service\QrCodeGenerator;
use PHPUnit\Framework\TestCase;

class QrCodeGeneratorTest extends TestCase
{
    public function testGenerateQrCodeAsBase64ReturnsBase64PngDataUri() : void
    {
        $generator = new QrCodeGenerator();

        $result = $generator->generateQrCodeAsBase64('https://medcore.pp.ua/lab-orders/ABC123');

        $this->assertIsString($result);
        $this->assertStringStartsWith('data:image/png;base64,', $result);
    }

    public function testGenerateQrCodeAsBase64ProducesDifferentOutputForDifferentInput() : void
    {
        $generator = new QrCodeGenerator();

        $first = $generator->generateQrCodeAsBase64('payload-one');
        $second = $generator->generateQrCodeAsBase64('payload-two');

        $this->assertNotSame($first, $second);
    }
}
