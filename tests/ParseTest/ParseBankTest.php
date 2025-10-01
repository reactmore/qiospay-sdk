<?php

namespace Tests\ParseTest;

use Tests\Support\TestCase;

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
