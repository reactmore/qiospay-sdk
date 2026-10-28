<?php

namespace Tests;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamInterface;
use Reactmore\QiosPay\Services\Products;
use Reactmore\SupportAdapter\Adapter\AdapterInterface;
use Tests\Support\TestCase;

class ProductsServiceTest extends TestCase
{
    /**
     * Get products without filters.
     */
    public function testGetProductReturnsFormattedResponse(): void
    {
        $payload = [
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

        $service = $this->makeServiceWithResponse(
            json_encode($payload)
        );

        $response = $service->getProduct();

        $this->assertTrue($response->success);
        $this->assertSame(200, $response->status_code);
        $this->assertSame('Request successful', $response->message);

        $this->assertIsArray($response->data);
        $this->assertCount(2, $response->data);

        $this->assertSame('BYRTSELQM', $response->data[0]['kode']);
        $this->assertSame('Telkomsel Omni', $response->data[0]['produk']);
        $this->assertSame(2050, $response->data[0]['harga']);
        $this->assertSame(1, $response->data[0]['status']);
    }

    /**
     * Get products with filters.
     */
    public function testGetProductWithFilters(): void
    {
        $payload = [
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
            [
                'kode'       => 'LISTTSELQM',
                'produk'     => 'Telkomsel Omni',
                'keterangan' => 'Cek List Telkomsel Combo Sakti',
                'harga'      => 0,
                'status'     => 1,
            ],
        ];

        $service = $this->makeServiceWithResponse(
            json_encode($payload)
        );

        $filters = [
            'produk' => 'Telkomsel Omni',
        ];

        $response = $service->getProduct($filters, 1);

        $this->assertTrue($response->success);
        $this->assertSame(200, $response->status_code);

        $this->assertIsArray($response->data);
        $this->assertCount(3, $response->data);

        $this->assertSame('BYRTSELQM', $response->data[0]['kode']);
        $this->assertSame('CEKTSELQM', $response->data[1]['kode']);
        $this->assertSame('LISTTSELQM', $response->data[2]['kode']);
    }

    /**
     * Get products with callback.
     */
    public function testGetProductWithCallback(): void
    {
        $payload = [
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
            [
                'kode'       => 'LISTTSELQM',
                'produk'     => 'Telkomsel Omni',
                'keterangan' => 'Cek List Telkomsel Combo Sakti',
                'harga'      => 0,
                'status'     => 1,
            ],
        ];

        $service = $this->makeServiceWithResponse(
            json_encode($payload)
        );

        $filters = [
            'produk' => 'Telkomsel Omni',
        ];

        $callback = static function (array $products): array {
            return array_filter(
                $products,
                static fn(array $item): bool => $item['harga'] > 0
            );
        };

        $response = $service->getProduct($filters, 1, $callback);

        $this->assertTrue($response->success);
        $this->assertSame(200, $response->status_code);

        $this->assertIsArray($response->data);
        $this->assertCount(1, $response->data);

        $product = array_values($response->data)[0];

        $this->assertSame('BYRTSELQM', $product['kode']);
        $this->assertSame('Telkomsel Omni', $product['produk']);
        $this->assertSame(2050, $product['harga']);
    }

    public function testGetCategoriesReturnsEmptyArray(): void
    {
        $products = $this->qiospay->products();

        $filters = [];


        $response = $products->getCategories($filters);

        dd($response);

        $this->assertTrue($response->success);
        $this->assertSame(200, $response->status_code);
        $this->assertSame('Request successful', $response->message);
        $this->assertSame([], $response->data);
    }

    /**
     * Get unique product categories.
     */
    public function testGetCategories(): void
    {
        $payload = [
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
            [
                'kode'       => 'LISTTSELQM',
                'produk'     => 'Telkomsel Omni',
                'keterangan' => 'Cek List Telkomsel Combo Sakti',
                'harga'      => 0,
                'status'     => 1,
            ],
        ];

        $service = $this->makeServiceWithResponse(
            json_encode($payload)
        );

        // maxPage = 1 supaya getAll() hanya mengambil halaman pertama.
        $response = $service->getCategories([], 1);

        $this->assertTrue($response->success);
        $this->assertSame(200, $response->status_code);
        $this->assertSame('Request successful', $response->message);

        $this->assertIsArray($response->data);
        $this->assertCount(1, $response->data);

        // getCategories() mengambil field "produk"
        // dan menghapus kategori yang duplikat.
        $this->assertSame(
            ['Telkomsel Omni'],
            $response->data
        );
    }

    /**
     * Create Products service with mocked HTTP response.
     */
    private function makeServiceWithResponse(string $body): Products
    {
        $mockStream = $this->createMock(StreamInterface::class);

        $mockStream
            ->method('getContents')
            ->willReturn($body);

        $mockResponse = $this->createMock(ResponseInterface::class);

        $mockResponse
            ->method('getBody')
            ->willReturn($mockStream);

        $mockAdapter = $this->createMock(AdapterInterface::class);

        $mockAdapter
            ->method('get')
            ->willReturn($mockResponse);

        return new Products(
            $mockAdapter,
            $this->config
        );
    }
}
