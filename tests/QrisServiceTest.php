<?php

namespace Tests;

use Reactmore\QiosPay\Services\Qris;
use Tests\Support\TestCase;

final class QrisServiceTest extends TestCase
{
    public function testQrisServiceInstance(): void
    {
        $qrisService = $this->qiospay->qris();

        $this->assertInstanceOf(Qris::class, $qrisService);
    }

    public function testCreateQrisWithAmount(): void
    {
        $qrisService = $this->qiospay->qris();

        $response = $qrisService->createQris([
            'amount' => 15000,
        ]);

        $data = $response->getData();

        $this->assertNotEmpty($data);

        $this->assertArrayHasKey('qris_string', $data);
        $this->assertArrayHasKey('qris_image', $data);

        $this->assertNotEmpty($data['qris_string']);
        $this->assertNotEmpty($data['qris_image']);

        // Pastikan QRIS menjadi dynamic
        $this->assertStringContainsString('010212', $data['qris_string']);

        // Pastikan nominal Rp15.000 masuk ke tag 54
        $this->assertStringContainsString('540515000', $data['qris_string']);

        // Pastikan image berupa Data URI PNG
        $this->assertStringStartsWith(
            'data:image/png;base64,',
            $data['qris_image']
        );

        // Pastikan base64 valid
        $image = base64_decode(
            str_replace('data:image/png;base64,', '', $data['qris_image']),
            true
        );

        $this->assertNotFalse($image);

        // PNG signature
        $this->assertStringStartsWith(
            "\x89PNG\r\n\x1a\n",
            $image
        );
    }

    public function testCreateQrisWithoutAmountReturnsStaticQris(): void
    {
        $qrisService = $this->qiospay->qris();

        $response = $qrisService->createQris();

        $data = $response->getData();

        $this->assertArrayHasKey('qris_string', $data);
        $this->assertArrayHasKey('qris_image', $data);

        $this->assertSame(
            $this->config->qrisString,
            $data['qris_string']
        );

        $this->assertStringStartsWith(
            'data:image/png;base64,',
            $data['qris_image']
        );
    }

    public function testCreateQrisWithServiceFeePercentage(): void
    {
        $qrisService = $this->qiospay->qris();

        $response = $qrisService->createQris([
            'amount'      => 15000,
            'service_fee' => true,
            'fee_type'    => 'persen',
            'fee_value'   => 0.7,
        ]);

        $data = $response->getData();

        $this->assertArrayHasKey('qris_string', $data);

        // Fee 0.7
        $this->assertStringContainsString(
            '55020357',
            $data['qris_string']
        );

        $this->assertStringContainsString(
            '0.7',
            $data['qris_string']
        );
    }

    public function testCreateQrisWithServiceFeeRupiah(): void
    {
        $qrisService = $this->qiospay->qris();

        $response = $qrisService->createQris([
            'amount'      => 15000,
            'service_fee' => true,
            'fee_type'    => 'rupiah',
            'fee_value'   => 500,
        ]);

        $data = $response->getData();

        $this->assertArrayHasKey('qris_string', $data);

        // Fee rupiah menggunakan code 55020256
        $this->assertStringContainsString(
            '55020256',
            $data['qris_string']
        );

        $this->assertStringContainsString(
            '500',
            $data['qris_string']
        );
    }

    public function testCreateQrisCanSaveImage(): void
    {
        $qrisService = $this->qiospay->qris();

        $path = WRITEPATH . 'tests/qris';

        $response = $qrisService->createQris([
            'amount' => 15000,
            'path'   => $path,
        ]);

        $data = $response->getData();

        $this->assertArrayHasKey('qris_path', $data);

        $this->assertFileExists($data['qris_path']);

        $this->assertGreaterThan(
            0,
            filesize($data['qris_path'])
        );

        // Pastikan file benar-benar PNG
        $this->assertSame(
            'image/png',
            mime_content_type($data['qris_path'])
        );

        // Cleanup
        unlink($data['qris_path']);
    }

    public function testGetMutation(): void
    {
        $qrisService = $this->qiospay->qris();

        $response = $qrisService->getMutation();

        $data = $response->getData();

        $this->assertNotEmpty($data);
    }
}
