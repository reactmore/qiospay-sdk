<?php

namespace Reactmore\QiosPay\Services\Parsers;

class ResponseParser
{
    /**
     * Regex map dengan handler masing-masing.
     */
    private array $patterns = [];

    public function __construct()
    {
        $this->patterns = [
            // Harga Max Rejected
            '/^R#(\S+)\s+Saldo\s+(\w+)\s+([\d\.]+)\s+([A-Z]+\d+)\.(\d+),\s+(.*?)\s+Saldo\s+([\d\.,]+)\s*@\s*(.+)$/u' => [$this, 'parseRejectedHargaMax'],

            // Voucher tidak tersedia
            '/^R#(\S+)\s+(\S+)\s+(\S+)\s+(Gagal, Voucher tidak tersedia,.*?)\s+Saldo\s([\d\.,]+)\s*@\s*(.+)$/u' => [$this, 'parseNormalSixGroups'],

            // Nomor HP tidak benar
            '/^R#(\S+)\s+(\S+)\s+(\S+)\s+(Nomor HP tidak benar\..*?)\s+Saldo\s([\d\.,]+)\s*@\s*(.+)$/u' => [$this, 'parseNormalSixGroups'],

            // Normal (variasi)
            '/^R#(\S+)\s+(\S+)\s+(\S+),\s+(.*?)\s+Saldo\s([\d\.,]+)\s*@\s*(.+)$/u' => [$this, 'parseNormalSixGroups'],
            '/^R#(\S+)\s+(\S+)\s+(\S+)\s+(.*?)\s+Saldo\s([\d\.,]+)\s*@\s*(.+)$/u' => [$this, 'parseNormalSixGroups'],
            '/^R#(\S+)\s+(\S+)\s+(\S+)(?:,)?\s+(.*?)\s+Saldo\s([\d\.,]+)\s*@\s*(.+)$/u' => [$this, 'parseNormalSixGroups'],

            // Fallback
            '/^R#(\S+)\s+(.*?),\s+(.*?)\s+Saldo\s([\d\.,]+)\s*@\s*(.+)$/u' => [$this, 'parseNormalFiveGroups'],
        ];
    }

    public function parse(string $body): array
    {
        $body = trim($body);

        foreach ($this->patterns as $regex => $handler) {
            if (preg_match($regex, $body, $matches)) {
                return $handler($matches, $body);
            }
        }

        // fallback kalau nggak match apapun
        return $this->defaultParsed($body);
    }


    private function parseRejectedHargaMax(array $m, string $raw): array
    {
        return $this->formatParsed([
            'trx_id'       => $m[1] ?? null,
            'product_code' => $m[4] ?? null,
            'dest'         => $m[5] ?? null,
            'status_msg'   => $m[6] ?? null,
            'saldo'        => $m[7] ?? null,
            'datetime'     => $m[8] ?? null,
        ], $raw);
    }

    private function parseNormalSixGroups(array $m, string $raw): array
    {
        return $this->formatParsed([
            'trx_id'       => $m[1] ?? null,
            'product_code' => $m[2] ?? null,
            'dest'         => $m[3] ?? null,
            'status_msg'   => $m[4] ?? null,
            'saldo'        => $m[5] ?? null,
            'datetime'     => $m[6] ?? null,
        ], $raw);
    }

    private function parseNormalFiveGroups(array $m, string $raw): array
    {
        $leftPart = trim($m[2] ?? '');
        $product  = $leftPart;
        $dest     = null;

        if ($leftPart !== '') {
            $tokens    = preg_split('/\s+/', $leftPart);
            $lastToken = end($tokens);
            if (preg_match('/\d/', $lastToken)) {
                array_pop($tokens);
                $product = implode(' ', $tokens);
                $dest    = $lastToken;
            }
        }

        return $this->formatParsed([
            'trx_id'       => $m[1] ?? null,
            'product_code' => $product,
            'dest'         => $dest,
            'status_msg'   => $m[3] ?? null,
            'saldo'        => $m[4] ?? null,
            'datetime'     => $m[5] ?? null,
        ], $raw);
    }

    private function defaultParsed(string $raw): array
    {
        return $this->formatParsed([], $raw);
    }

    private function formatParsed(array $data, string $raw): array
    {
        return array_merge([
            'trx_id'       => null,
            'product_code' => null,
            'dest'         => null,
            'status_msg'   => null,
            'saldo'        => null,
            'datetime'     => null,
            'raw'          => trim($raw),
        ], $data);
    }
}
