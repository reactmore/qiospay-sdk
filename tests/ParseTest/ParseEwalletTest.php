<?php

namespace Tests\ParseTest;

use Tests\Support\TestCase;

class ParseEwalletTest extends TestCase
{
    public function testParseShopeePayCheckAccount(): void
    {
        $message = "T#1900901 R#trx_1759288567, Alhamdulillah, SUKSES. Cek Produk Digital H2H-Cek Akun Shopeepay.085155092922. SN: ShopeePay-ANDRY SETYOSO. . Saldo 58270 - 0 = 58.270 @01\/10\/2025 10:16\r\nqiospay.id";

        $parsed = parseTransactionMessage($message);

        $this->assertSame('1900901', $parsed['trxid']);
        $this->assertSame('trx_1759288567', $parsed['refid']);
        $this->assertSame('SUKSES', $parsed['status']);
        $this->assertSame('085155092922', $parsed['account']);
        $this->assertStringContainsString('Cek Produk Digital H2H-Cek Akun Shopeepay', $parsed['product']);
        $this->assertSame('ShopeePay-ANDRY SETYOSO', $parsed['sn']);
        $this->assertNull($parsed['nominal']);
        $this->assertSame('58.270', $parsed['saldo']);
        $this->assertSame('01/10/2025 10:16', $parsed['datetime']);
    }

    public function testParseDanaCheckAccount(): void
    {
        $message = "T#1900886 R#trx_1759288215, Alhamdulillah, SUKSES. Cek Produk Digital H2H-Cek Akun Dana.085155092922. SN: DANA-ANDXX SETXXXX\/Nominal:1. . Saldo 58270 - 0 = 58.270 @01\/10\/2025 10:10\r\nqiospay.id";

        $parsed = parseTransactionMessage($message);

        $this->assertSame('1900886', $parsed['trxid']);
        $this->assertSame('trx_1759288215', $parsed['refid']);
        $this->assertSame('SUKSES', $parsed['status']);
        $this->assertSame('085155092922', $parsed['account']);
        $this->assertStringContainsString('Cek Produk Digital H2H-Cek Akun Dana', $parsed['product']);
        $this->assertStringContainsString('DANA-ANDXX SETXXXX/Nominal:1', $parsed['sn']);
        $this->assertNull($parsed['nominal']);
        $this->assertSame('58.270', $parsed['saldo']);
        $this->assertSame('01/10/2025 10:10', $parsed['datetime']);
    }

    public function testParseDanaTopupSuccess(): void
    {
        $message = "T#1899822 R#trx_1759243747, Alhamdulillah, SUKSES. DANA H2H-Saldo Dana 15.000.085155092922. SN: DanaTopup-DNID ANDXX SETXXXX\/15000\/2025093010121481030100166095304620436.. Saldo 73345 - 15075 = 58.270 @30\/09\/2025 21:55\r\nqiospay.id";

        $parsed = parseTransactionMessage($message);

        $this->assertSame('1899822', $parsed['trxid']);
        $this->assertSame('trx_1759243747', $parsed['refid']);
        $this->assertSame('SUKSES', $parsed['status']);
        $this->assertSame('085155092922', $parsed['account']);
        $this->assertSame('DANA H2H-Saldo Dana 15.000', $parsed['product']);
        $this->assertStringStartsWith('DanaTopup-DNID', $parsed['sn']);
        $this->assertSame('15000', $parsed['nominal']);
        $this->assertSame('58.270', $parsed['saldo']);
        $this->assertSame('30/09/2025 21:55', $parsed['datetime']);
    }

    public function testParseGopayCheckAccount(): void
    {
        $message = "T#1900948 R#trx_1759289623, Alhamdulillah, SUKSES. Cek Produk Digital H2H-Cek Akun Gopay.085155092922. SN: Nama:GOPAY Axxxx Sxxxxxx\/Nomor:085155092922. Saldo 58270 - 0 = 58.270 @01\/10\/2025 10:33\r\nqiospay.id";

        $parsed = parseTransactionMessage($message);

        $this->assertSame('1900948', $parsed['trxid']);
        $this->assertSame('trx_1759289623', $parsed['refid']);
        $this->assertSame('SUKSES', $parsed['status']);
        $this->assertSame('085155092922', $parsed['account']);
        $this->assertStringContainsString('Cek Produk Digital H2H-Cek Akun Gopay', $parsed['product']);
        $this->assertStringContainsString('Nama:GOPAY Axxxx Sxxxxxx', $parsed['sn']);
        $this->assertNull($parsed['nominal']);
        $this->assertSame('58.270', $parsed['saldo']);
        $this->assertSame('01/10/2025 10:33', $parsed['datetime']);
    }

    public function testParseGopayTopupSuccess(): void
    {
        $message = "T#1900971 R#trx_1759290204, Alhamdulillah, SUKSES. Gopay H2H-Gopay 10.000.085155092922. SN: GOPAY - 085155092922\/GOPAY Andry Setyoso\/10.000\/0120251001034330WpMtUZvGskID. Saldo 58270 - 10210 = 48.060 @01\/10\/2025 10:43\r\nqiospay.id";

        $parsed = parseTransactionMessage($message);

        $this->assertSame('1900971', $parsed['trxid']);
        $this->assertSame('trx_1759290204', $parsed['refid']);
        $this->assertSame('SUKSES', $parsed['status']);
        $this->assertSame('085155092922', $parsed['account']);
        $this->assertSame('Gopay H2H-Gopay 10.000', $parsed['product']);
        $this->assertSame('GOPAY - 085155092922/GOPAY Andry Setyoso/10.000/0120251001034330WpMtUZvGskID', $parsed['sn']);
        $this->assertSame('10000', $parsed['nominal']);
        $this->assertSame('48.060', $parsed['saldo']);
        $this->assertSame('01/10/2025 10:43', $parsed['datetime']);
    }

    public function testParseOvoCheckAccount(): void
    {
        $message = "T#1900955 R#trx_1759289909, Alhamdulillah, SUKSES. Cek Produk Digital H2H-Cek Akun OVO.085155092922. SN: OVO-ANDRY-SETYOSO\/Nominal=1\/REFF=412382096. Saldo 58270 - 0 = 58.270 @01\/10\/2025 10:38\r\nqiospay.id";

        $parsed = parseTransactionMessage($message);

        $this->assertSame('1900955', $parsed['trxid']);
        $this->assertSame('trx_1759289909', $parsed['refid']);
        $this->assertSame('SUKSES', $parsed['status']);
        $this->assertSame('085155092922', $parsed['account']);
        $this->assertSame('Cek Produk Digital H2H-Cek Akun OVO', $parsed['product']);
        $this->assertSame('OVO-ANDRY-SETYOSO/Nominal=1/REFF=412382096', $parsed['sn']);
        $this->assertNull($parsed['nominal']);
        $this->assertSame('58.270', $parsed['saldo']);
        $this->assertSame('01/10/2025 10:38', $parsed['datetime']);
    }

    public function testParseLinkAjaCheckAccount(): void
    {
        $message = "T#1900965 R#trx_1759290076, Alhamdulillah, SUKSES. Cek Produk Digital H2H-Cek Akun Linkaja.085155092922. SN: andry setyoso. Saldo 58270 - 0 = 58.270 @01\/10\/2025 10:41\r\nqiospay.id";

        $parsed = parseTransactionMessage($message);

        $this->assertSame('1900965', $parsed['trxid']);
        $this->assertSame('trx_1759290076', $parsed['refid']);
        $this->assertSame('SUKSES', $parsed['status']);
        $this->assertSame('085155092922', $parsed['account']);
        $this->assertSame('Cek Produk Digital H2H-Cek Akun Linkaja', $parsed['product']);
        $this->assertSame('andry setyoso', $parsed['sn']);
        $this->assertNull($parsed['nominal']);
        $this->assertSame('58.270', $parsed['saldo']);
        $this->assertSame('01/10/2025 10:41', $parsed['datetime']);
    }
}
