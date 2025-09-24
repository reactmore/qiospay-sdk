<?php

if (!function_exists('parse_qiospay_message')) {
    /**
     * Parse QiosPay H2H callback message menjadi array terstruktur
     *
     * @param string $message
     * @return array
     */
    function parse_qiospay_message(string $message): array
    {
        $result = [
            'trxid'     => null,
            'status'    => null,
            'phone'     => null,
            'product'   => null,
            'sn'        => null,
            'nominal'   => null,
            'saldo'     => null,
            'datetime'  => null,
            'note'      => null,
        ];

        // ambil trxid (R#...)
        if (preg_match('/R#([a-zA-Z0-9_]+)/', $message, $m)) {
            $result['trxid'] = $m[1];
        }

        // cek status (SUKSES / GAGAL)
        if (stripos($message, 'SUKSES') !== false) {
            $result['status'] = 'SUKSES';
        } elseif (stripos($message, 'GAGAL') !== false) {
            $result['status'] = 'GAGAL';
        }

        // ambil nomor hp
        if (preg_match('/\.(08[0-9]+)/', $message, $m)) {
            $result['phone'] = $m[1];
        }

        // ambil SN jika ada
        if (preg_match('/SN:\s*([^ ]+)/', $message, $m)) {
            $result['sn'] = $m[1];
        }

        // ambil nominal
        if (preg_match('/Nominal:([0-9\.]+)/', $message, $m)) {
            $result['nominal'] = $m[1];
        }

        // ambil saldo
        if (preg_match('/Saldo(?:\s*[0-9\-=\s]*)?:\s*([0-9\.]+)/', $message, $m)) {
            $result['saldo'] = $m[1];
        } elseif (preg_match('/Saldo\s+[0-9\-=\s]*=\s*([0-9\.]+)/', $message, $m)) {
            $result['saldo'] = $m[1];
        }

        // ambil datetime (jika ada format dd/mm/yyyy hh:mm)
        if (preg_match('/@(\d{2}\/\d{2}\/\d{4}\s+\d{2}:\d{2})/', $message, $m)) {
            $result['datetime'] = $m[1];
        }

        // ambil keterangan tambahan (Ket:)
        if (preg_match('/Ket:\s*(.+)$/', $message, $m)) {
            $result['note'] = trim($m[1]);
        }

        return $result;
    }
}
