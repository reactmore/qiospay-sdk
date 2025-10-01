<?php

namespace Tests;

use Tests\Support\TestCase;
use Reactmore\QiosPay\Services\Transactions;

class TransactionsServiceTest extends TestCase
{
    public function testParseResponseHargaMaxRejected(): void
    {
        $body = "R#trx_1758979420 Saldo Dana 10.000 DANA10.085155092922, diabaikan karena Harga Voucher 10.060 lebih besar dari Harga Max anda 1.000. Saldo 71.232 @27/09/2025 20:23";

        $mockStream = $this->createMock(\Psr\Http\Message\StreamInterface::class);
        $mockStream->method('__toString')->willReturn($body);

        $mockResponse = $this->createMock(\Psr\Http\Message\ResponseInterface::class);
        $mockResponse->method('getBody')->willReturn($mockStream);

        $mockAdapter = $this->createMock(\Reactmore\SupportAdapter\Adapter\AdapterInterface::class);
        $mockAdapter->expects($this->once())
            ->method('get')
            ->willReturn($mockResponse);

        $service  = new Transactions($mockAdapter, $this->config);
        $response = $service->h2h(['product' => 'DANA10', 'dest' => '085155092922']);

        $this->assertTrue($response->success);
        $this->assertSame('trx_1758979420', $response->data['trx_id']);
        $this->assertSame('DANA10', $response->data['product_code']);
        $this->assertSame('085155092922', $response->data['dest']);
        $this->assertSame('GAGAL', $response->data['transaction_status']);
        $this->assertStringContainsString('diabaikan karena Harga Voucher', $response->data['status_msg']);
        $this->assertSame('71.232', $response->data['saldo']);
        $this->assertSame('27/09/2025 20:23', $response->data['datetime']);
    }

    public function testParseResponseProcessed(): void
    {
        $body = "R#trx_1758141872 cekd 085155092922, Mohon tunggu transaksi sedang diproses. Saldo 100.543 @ 18/09/2025 10:44";

        $mockStream = $this->createMock(\Psr\Http\Message\StreamInterface::class);
        $mockStream->method('__toString')->willReturn($body);

        $mockResponse = $this->createMock(\Psr\Http\Message\ResponseInterface::class);
        $mockResponse->method('getBody')->willReturn($mockStream);

        $mockAdapter = $this->createMock(\Reactmore\SupportAdapter\Adapter\AdapterInterface::class);
        $mockAdapter->expects($this->once())
            ->method('get')
            ->willReturn($mockResponse);

        $service  = new Transactions($mockAdapter, $this->config);
        $response = $service->h2h(['product' => 'DANA', 'dest' => '085155092922']);

        $this->assertTrue($response->success);
        $this->assertSame('trx_1758141872', $response->data['trx_id']);
        $this->assertSame('cekd', $response->data['product_code']);
        $this->assertSame('085155092922', $response->data['dest']);
        $this->assertSame('SUKSES', $response->data['transaction_status']);
        $this->assertStringContainsString('Mohon tunggu transaksi sedang', $response->data['status_msg']);
        $this->assertSame('100.543', $response->data['saldo']);
        $this->assertSame('18/09/2025 10:44', $response->data['datetime']);
    }

    public function testParseResponseFailed(): void
    {
        $body = "R#trx_1758978632 CEKBYUQM 085155092922, GAGAL. Kode produk salah. Saldo 71.232 @ 27/09/2025 20:10";

        $mockStream = $this->createMock(\Psr\Http\Message\StreamInterface::class);
        $mockStream->method('__toString')->willReturn($body);

        $mockResponse = $this->createMock(\Psr\Http\Message\ResponseInterface::class);
        $mockResponse->method('getBody')->willReturn($mockStream);

        $mockAdapter = $this->createMock(\Reactmore\SupportAdapter\Adapter\AdapterInterface::class);
        $mockAdapter->expects($this->once())
            ->method('get')
            ->willReturn($mockResponse);

        $service  = new Transactions($mockAdapter, $this->config);
        $response = $service->h2h(['product' => 'CEKBYUQM', 'dest' => '085155092922']);

        $this->assertTrue($response->success);
        $this->assertSame('trx_1758978632', $response->data['trx_id']);
        $this->assertSame('CEKBYUQM', $response->data['product_code']);
        $this->assertSame('085155092922', $response->data['dest']);
        $this->assertSame('GAGAL', $response->data['transaction_status']);
        $this->assertStringContainsString('GAGAL.', $response->data['status_msg']);
        $this->assertSame('71.232', $response->data['saldo']);
        $this->assertSame('27/09/2025 20:10', $response->data['datetime']);
    }

    public function testParseVoucherNotAvailable(): void
    {
        $body = "R#trx_1759238656 DANA15 6285155092922 Gagal, Voucher tidak tersedia, silakan pilih nominal lainnya.. Saldo 88.420 @ 30/09/2025 20:24";

        $mockStream = $this->createMock(\Psr\Http\Message\StreamInterface::class);
        $mockStream->method('__toString')->willReturn($body);

        $mockResponse = $this->createMock(\Psr\Http\Message\ResponseInterface::class);
        $mockResponse->method('getBody')->willReturn($mockStream);

        $mockAdapter = $this->createMock(\Reactmore\SupportAdapter\Adapter\AdapterInterface::class);
        $mockAdapter->expects($this->once())
            ->method('get')
            ->willReturn($mockResponse);

        $service  = new Transactions($mockAdapter, $this->config);
        $response = $service->h2h(['product' => 'DANA15', 'dest' => '6285155092922']);

        $this->assertTrue($response->success);
        $this->assertSame('trx_1759238656', $response->data['trx_id']);
        $this->assertSame('DANA15', $response->data['product_code']);
        $this->assertSame('6285155092922', $response->data['dest']);
        $this->assertSame('GAGAL', $response->data['transaction_status']);
        $this->assertStringContainsString('Gagal, Voucher tidak tersedia, silakan pilih nominal lainnya..', $response->data['status_msg']);
        $this->assertSame('88.420', $response->data['saldo']);
        $this->assertSame('30/09/2025 20:24', $response->data['datetime']);
    }

    public function testParseResponsePendingProcessed(): void
    {
        $body = "R#trx_1758143250 danabqsp 085155092922 Transaksi sebelumnya ke ID Pelanggan 085155092922 masih dalam proses.. Saldo 85.343 @ 18/09/2025 04:07";

        $mockStream = $this->createMock(\Psr\Http\Message\StreamInterface::class);
        $mockStream->method('__toString')->willReturn($body);

        $mockResponse = $this->createMock(\Psr\Http\Message\ResponseInterface::class);
        $mockResponse->method('getBody')->willReturn($mockStream);

        $mockAdapter = $this->createMock(\Reactmore\SupportAdapter\Adapter\AdapterInterface::class);
        $mockAdapter->expects($this->once())
            ->method('get')
            ->willReturn($mockResponse);

        $service  = new Transactions($mockAdapter, $this->config);
        $response = $service->h2h(['product' => 'danabqsp', 'dest' => '085155092922']);

        $this->assertTrue($response->success);
        $this->assertSame('trx_1758143250', $response->data['trx_id']);
        $this->assertSame('085155092922', $response->data['dest']);
        $this->assertSame('GAGAL', $response->data['transaction_status']);
        $this->assertStringContainsString('danabqsp', $response->data['product_code']);
        $this->assertStringContainsString('Transaksi sebelumnya', $response->data['status_msg']);
        $this->assertSame('85.343', $response->data['saldo']);
        $this->assertSame('18/09/2025 04:07', $response->data['datetime']);
    }

    public function testParsePhoneNumberInvalid(): void
    {
        $body = "R#trx_1759240064 DANA15 6285155092922 Nomor HP tidak benar. Saldo 88.420 @ 30/09/2025 20:47";

        $mockStream = $this->createMock(\Psr\Http\Message\StreamInterface::class);
        $mockStream->method('__toString')->willReturn($body);

        $mockResponse = $this->createMock(\Psr\Http\Message\ResponseInterface::class);
        $mockResponse->method('getBody')->willReturn($mockStream);

        $mockAdapter = $this->createMock(\Reactmore\SupportAdapter\Adapter\AdapterInterface::class);
        $mockAdapter->expects($this->once())
            ->method('get')
            ->willReturn($mockResponse);

        $service  = new Transactions($mockAdapter, $this->config);
        $response = $service->h2h(['product' => 'DANA15', 'dest' => '6285155092922']);

        $this->assertTrue($response->success);
        $this->assertSame('trx_1759240064', $response->data['trx_id']);
        $this->assertSame('DANA15', $response->data['product_code']);
        $this->assertSame('6285155092922', $response->data['dest']);
        $this->assertSame('GAGAL', $response->data['transaction_status']);
        $this->assertStringContainsString('Nomor HP tidak', $response->data['status_msg']);
        $this->assertSame('88.420', $response->data['saldo']);
        $this->assertSame('30/09/2025 20:47', $response->data['datetime']);
    }
}
