<?php

if (!function_exists('parseTransactionMessage')) {
    function parseTransactionMessage(string $message): array
    {
        // $message 
        // 'T#1899822 R#trx_1759243747, Alhamdulillah, SUKSES. DANA H2H-Saldo Dana 15.000.085155092922. SN: DanaTopup-DNID ANDXX SETXXXX\/15000\/2025093010121481030100166095304620436.. Saldo 73345 - 15075 = 58.270 @30\/09\/2025 21:55\r\nqiospay.id'

        // decode escape $message
        $clean = str_replace(['\\/', '\r', '\n'], ['/', '', ''], $message);
        $clean = trim($clean);
        
        $result = [
            "trxid"    => null,
            "refid"    => null,
            "status"   => null,
            "phone"    => null,
            "product"  => null,
            "sn"       => null,
            "nominal"  => null,
            "saldo"    => null,
            "datetime" => null,
            "note"     => null,
        ];

        // trxid
        if (preg_match('/T#(\d+)/', $clean, $m)) {
            $result['trxid'] = $m[1];
        }

        // refid
        if (preg_match('/R#([a-zA-Z0-9_]+)/', $clean, $m)) {
            $result['refid'] = $m[1];
        }

        // status
        if (preg_match('/\b(SUKSES|GAGAL|PENDING)\b/i', $clean, $m)) {
            $result['status'] = strtoupper($m[1]);
        }

        // phone
        if (preg_match('/\b(08[0-9]{8,13})\b/', $clean, $m)) {
            $result['phone'] = $m[1];
        }

        // product (antara SUKSES. ... . SN:)
        if (preg_match('/SUKSES\.\s*(.+?)\.\d{10,13}\.\s*SN:/i', $clean, $m)) {
            $result['product'] = trim($m[1]);
        }

        // SN
        if (preg_match('/SN:\s*(.+?)\.\./', $clean, $m)) {
            $result['sn'] = trim($m[1]) . '.';
        }

        // nominal (cari /xxxx/ di SN atau angka bertitik 15.000 → 15000)
        if ($result['sn'] && preg_match('/\/(\d{4,7})\//', $result['sn'], $m)) {
            $result['nominal'] = $m[1];
        } elseif ($result['product'] && preg_match('/(\d{1,3}(?:\.\d{3})+)/', $result['product'], $m)) {
            $result['nominal'] = str_replace('.', '', $m[1]);
        }

        // saldo
        if (preg_match('/=\s*([\d\.]+)\s*@/', $clean, $m)) {
            $result['saldo'] = $m[1];
        }

        // datetime
        if (preg_match('/@(\d{2}\/\d{2}\/\d{4}\s+\d{2}:\d{2})/', $clean, $m)) {
            $result['datetime'] = $m[1];
        }

        $result['raw'] = $clean;

        return $result;
    }
}
