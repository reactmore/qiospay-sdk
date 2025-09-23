<?php

namespace Tests;

use Tests\Support\TestCase;

class ProductsServiceTest extends TestCase
{
    public function testGetProductReturnsFormattedResponse(): void
    {
        $payloadData = [
            [
                'kode'       => 'BYRTSELQM',
                'produk'     => 'Telkomsel Omni',
                'keterangan' => 'Bayar Telkomsel Combo Sakti',
                'harga'      => 2050,
                'status'     => 1,
            ],
            [
                'kode'       => 'CEKTSELQM',
                'produk'     => 'Telkomsel Omni',
                'keterangan' => 'Cek Harga Telkomsel Combo Sakti',
                'harga'      => 0,
                'status'     => 1,
            ],
        ];

        // Mock body response
        $mockStream = $this->createMock(\Psr\Http\Message\StreamInterface::class);
        $mockStream->method('getContents')
            ->willReturn(json_encode($payloadData));

        // Mock PSR-7 response
        $mockResponse = $this->createMock(\Psr\Http\Message\ResponseInterface::class);
        $mockResponse->method('getBody')
            ->willReturn($mockStream);

        // Mock adapter
        $mockAdapter = $this->createMock(\Reactmore\SupportAdapter\Adapter\AdapterInterface::class, $this->config);
        $mockAdapter->expects($this->once())
            ->method('get')
            ->with('admin/modules/mapping/harga/1/reseller')
            ->willReturn($mockResponse);

        $service  = new \Reactmore\QiosPay\Services\Products($mockAdapter, $this->config);
        $response = $service->getProduct();

        // Cek struktur sesuai format ResponseFormatter
        $this->assertTrue($response->success);
        $this->assertSame(200, $response->status_code);
        $this->assertSame('Request successful', $response->message);
        $this->assertIsArray($response->data);
        $this->assertCount(2, $response->data);

        $this->assertSame('BYRTSELQM', $response->data[0]['kode']);
        $this->assertSame(2050, $response->data[0]['harga']);
    }
}
