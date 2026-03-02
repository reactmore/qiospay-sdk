<?php

namespace Tests;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamInterface;
use Reactmore\QiosPay\Services\Transactions;
use Reactmore\SupportAdapter\Adapter\AdapterInterface;
use Tests\Support\TestCase;

class TransactionsServiceTest extends TestCase
{
    /**
     * @dataProvider transactionResponseProvider
     */
    public function testParseTransactionResponse(string $body, array $request, array $expected): void
    {
        $service = $this->makeServiceWithResponseBody($body);
        $response = $service->h2h($request);

        $this->assertTrue($response->success);
        $this->assertSame($expected['trx_id'], $response->data['trx_id']);
        $this->assertSame($expected['product_code'], $response->data['product_code']);
        $this->assertSame($expected['dest'], $response->data['dest']);
        $this->assertSame($expected['transaction_status'], $response->data['transaction_status']);
        $this->assertStringContainsString($expected['status_msg_contains'], $response->data['status_msg']);
        $this->assertSame($expected['saldo'], $response->data['saldo']);
        $this->assertSame($expected['datetime'], $response->data['datetime']);
    }

    public static function transactionResponseProvider(): array
    {
        return [
            'harga max rejected' => [
                'body' => 'R#trx_1758979420 Saldo Dana 10.000 DANA10.085155092922, diabaikan karena Harga Voucher 10.060 lebih besar dari Harga Max anda 1.000. Saldo 71.232 @27/09/2025 20:23',
                'request' => ['product' => 'DANA10', 'dest' => '085155092922'],
                'expected' => [
                    'trx_id' => 'trx_1758979420',
                    'product_code' => 'DANA10',
                    'dest' => '085155092922',
                    'transaction_status' => 'GAGAL',
                    'status_msg_contains' => 'diabaikan karena Harga Voucher',
                    'saldo' => '71.232',
                    'datetime' => '27/09/2025 20:23',
                ],
            ],
            'processed_top_up_pulsa' => [
                'body' => 'R#INV-1772362352 TFBCA10 0953955315, Mohon tunggu transaksi sedang diproses. Saldo 18.848 @ 02/03/2026 01:52',
                'request' => ['product' => 'TFBCA10', 'dest' => '0953955315'],
                'expected' => [
                    'trx_id' => 'INV-1772362352',
                    'product_code' => 'TFBCA10',
                    'dest' => '0953955315',
                    'transaction_status' => 'PROCESS',
                    'status_msg_contains' => 'Mohon tunggu transaksi sedang',
                    'saldo' => '18.848',
                    'datetime' => '02/03/2026 01:52',
                ],
            ],
            'processed' => [
                'body' => 'R#trx_1758141872 cekd 085155092922, Mohon tunggu transaksi sedang diproses. Saldo 100.543 @ 18/09/2025 10:44',
                'request' => ['product' => 'DANA', 'dest' => '085155092922'],
                'expected' => [
                    'trx_id' => 'trx_1758141872',
                    'product_code' => 'cekd',
                    'dest' => '085155092922',
                    'transaction_status' => 'PROCESS',
                    'status_msg_contains' => 'Mohon tunggu transaksi sedang',
                    'saldo' => '100.543',
                    'datetime' => '18/09/2025 10:44',
                ],
            ],
            'failed' => [
                'body' => 'R#trx_1758978632 CEKBYUQM 085155092922, GAGAL. Kode produk salah. Saldo 71.232 @ 27/09/2025 20:10',
                'request' => ['product' => 'CEKBYUQM', 'dest' => '085155092922'],
                'expected' => [
                    'trx_id' => 'trx_1758978632',
                    'product_code' => 'CEKBYUQM',
                    'dest' => '085155092922',
                    'transaction_status' => 'GAGAL',
                    'status_msg_contains' => 'GAGAL.',
                    'saldo' => '71.232',
                    'datetime' => '27/09/2025 20:10',
                ],
            ],
            'voucher not available' => [
                'body' => 'R#trx_1759238656 DANA15 6285155092922 Gagal, Voucher tidak tersedia, silakan pilih nominal lainnya.. Saldo 88.420 @ 30/09/2025 20:24',
                'request' => ['product' => 'DANA15', 'dest' => '6285155092922'],
                'expected' => [
                    'trx_id' => 'trx_1759238656',
                    'product_code' => 'DANA15',
                    'dest' => '6285155092922',
                    'transaction_status' => 'GAGAL',
                    'status_msg_contains' => 'Voucher tidak tersedia',
                    'saldo' => '88.420',
                    'datetime' => '30/09/2025 20:24',
                ],
            ],
            'pending process from previous trx' => [
                'body' => 'R#trx_1758143250 danabqsp 085155092922 Transaksi sebelumnya ke ID Pelanggan 085155092922 masih dalam proses.. Saldo 85.343 @ 18/09/2025 04:07',
                'request' => ['product' => 'danabqsp', 'dest' => '085155092922'],
                'expected' => [
                    'trx_id' => 'trx_1758143250',
                    'product_code' => 'danabqsp',
                    'dest' => '085155092922',
                    'transaction_status' => 'GAGAL',
                    'status_msg_contains' => 'Transaksi sebelumnya',
                    'saldo' => '85.343',
                    'datetime' => '18/09/2025 04:07',
                ],
            ],
            'phone number invalid' => [
                'body' => 'R#trx_1759240064 DANA15 6285155092922 Nomor HP tidak benar. Saldo 88.420 @ 30/09/2025 20:47',
                'request' => ['product' => 'DANA15', 'dest' => '6285155092922'],
                'expected' => [
                    'trx_id' => 'trx_1759240064',
                    'product_code' => 'DANA15',
                    'dest' => '6285155092922',
                    'transaction_status' => 'GAGAL',
                    'status_msg_contains' => 'Nomor HP tidak benar',
                    'saldo' => '88.420',
                    'datetime' => '30/09/2025 20:47',
                ],
            ],
            'insufficient balance' => [
                'body' => 'R#trx_1759315231 DANA15 085155092922, Saldo tidak mencukupi. Saldo 5.710 @ 01/10/2025 17:40',
                'request' => ['product' => 'DANA15', 'dest' => '085155092922'],
                'expected' => [
                    'trx_id' => 'trx_1759315231',
                    'product_code' => 'DANA15',
                    'dest' => '085155092922',
                    'transaction_status' => 'GAGAL',
                    'status_msg_contains' => 'Saldo tidak mencukupi',
                    'saldo' => '5.710',
                    'datetime' => '01/10/2025 17:40',
                ],
            ],
        ];
    }

    private function makeServiceWithResponseBody(string $body): Transactions
    {
        $mockStream = $this->createMock(StreamInterface::class);
        $mockStream->method('__toString')->willReturn($body);

        $mockResponse = $this->createMock(ResponseInterface::class);
        $mockResponse->method('getBody')->willReturn($mockStream);

        $mockAdapter = $this->createMock(AdapterInterface::class);
        $mockAdapter->expects($this->once())
            ->method('get')
            ->willReturn($mockResponse);

        return new Transactions($mockAdapter, $this->config);
    }
}
