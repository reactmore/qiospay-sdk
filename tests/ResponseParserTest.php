<?php

namespace Tests;

use Reactmore\QiosPay\Services\Parsers\ResponseParser;
use Tests\Support\TestCase;

class ResponseParserTest extends TestCase
{
    /**
     * @dataProvider statusMessageProvider
     */
    public function testDetectTransactionStatusFromMessage(string $body, string $expectedStatus): void
    {
        $parser = new ResponseParser();
        $parsed = $parser->parse($body);

        $this->assertSame($expectedStatus, $parsed['transaction_status']);
    }

    public static function statusMessageProvider(): array
    {
        return [
            'success keyword' => [
                'body' => 'R#trx_01 CEK 08123, Transaksi berhasil. Saldo 10.000 @ 01/01/2026 10:00',
                'expectedStatus' => 'SUKSES',
            ],
            'pending keyword' => [
                'body' => 'R#trx_02 CEK 08123, Mohon tunggu transaksi sedang diproses. Saldo 10.000 @ 01/01/2026 10:00',
                'expectedStatus' => 'PROCESS',
            ],
            'failed keyword' => [
                'body' => 'R#trx_03 CEK 08123, Gagal karena sistem error. Saldo 10.000 @ 01/01/2026 10:00',
                'expectedStatus' => 'GAGAL',
            ],
            'unknown message' => [
                'body' => 'R#trx_04 CEK 08123, Menunggu pembaruan status. Saldo 10.000 @ 01/01/2026 10:00',
                'expectedStatus' => 'UNKNOWN',
            ],
        ];
    }
}
