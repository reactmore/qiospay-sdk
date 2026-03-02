<?php

namespace Tests\ParseTest;

use Tests\Support\TestCase;
use Reactmore\QiosPay\Services\Transactions;

class ParseBankTest extends TestCase
{
    public function testParseCheckAccountBCA(): void
    {
        $message = "T#1901002 R#trx_1759290857, Alhamdulillah, SUKSES. Bank BCA-Cek Rekening BCA.0953955315. SN: ANDRY SETYOSO. Saldo 48060 - 0 = 48.060 @01\/10\/2025 10:54\r\nqiospay.id";

        $parsed = parseTransactionMessage($message);

        $this->assertSame('1901002', $parsed['trxid']);
        $this->assertSame('trx_1759290857', $parsed['refid']);
        $this->assertSame('SUKSES', $parsed['status']);
        $this->assertSame('0953955315', $parsed['account']);
        $this->assertSame('Bank BCA-Cek Rekening BCA', $parsed['product']);
        $this->assertStringStartsWith('ANDRY SETYOSO', $parsed['sn']);
        $this->assertNull($parsed['nominal']);
        $this->assertSame('48.060', $parsed['saldo']);
        $this->assertSame('01/10/2025 10:54', $parsed['datetime']);
    }

    public function testParseResponseProcessed(): void
    {
        $body = "R#INV-1772362352 TFBCA10 0953955315, Mohon tunggu transaksi sedang diproses. Saldo 18.848 @ 02\/03\/2026 01:52";

        $mockStream = $this->createMock(\Psr\Http\Message\StreamInterface::class);
        $mockStream->method('__toString')->willReturn($body);

        $mockResponse = $this->createMock(\Psr\Http\Message\ResponseInterface::class);
        $mockResponse->method('getBody')->willReturn($mockStream);

        $mockAdapter = $this->createMock(\Reactmore\SupportAdapter\Adapter\AdapterInterface::class);
        $mockAdapter->expects($this->once())
            ->method('get')
            ->willReturn($mockResponse);

        $service  = new Transactions($mockAdapter, $this->config);
        $response = $service->h2h(['product' => 'TFBCA10', 'dest' => '0953955315']);

        d($response);
    }

    public function testParseProsesTransferBCA(): void
    {
        $message = "R#INV-1772362352 TFBCA10 0953955315, Mohon tunggu transaksi sedang diproses. Saldo 18.848 @ 02\/03\/2026 01:52";

        $parsed = parseTransactionMessage($message);



        $this->assertSame('PROCESS', $parsed['status']);
    }

    public function testParseTransferBCA(): void
    {
        $message = "T#1901056 R#trx_1759291955, Alhamdulillah, SUKSES. Bank BCA-Saldo BCA 10.000.0953955315. SN: 2025100117611514797454387969. Saldo 48060 - 11900 = 36.160 @01\/10\/2025 11:12\r\nqiospay.id";

        $parsed = parseTransactionMessage($message);

        $this->assertSame('1901056', $parsed['trxid']);
        $this->assertSame('trx_1759291955', $parsed['refid']);
        $this->assertSame('SUKSES', $parsed['status']);
        $this->assertSame('0953955315', $parsed['account']);
        $this->assertSame('Bank BCA-Saldo BCA 10.000', $parsed['product']);
        $this->assertStringStartsWith('2025100117611514797454387969', $parsed['sn']);
        $this->assertSame('10000', $parsed['nominal']);
        $this->assertSame('36.160', $parsed['saldo']);
        $this->assertSame('01/10/2025 11:12', $parsed['datetime']);
    }
}
