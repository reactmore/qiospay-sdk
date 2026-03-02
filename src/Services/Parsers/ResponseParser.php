<?php

namespace Reactmore\QiosPay\Services\Parsers;

class ResponseParser
{
    private const STATUS_FAILED = 'GAGAL';
    private const STATUS_SUCCESS = 'SUKSES';
    private const STATUS_PROCESS = 'PROCESS';
    private const STATUS_UNKNOWN = 'UNKNOWN';

    /**
     * Regex map dengan handler masing-masing.
     *
     * @var array<string, callable>
     */
    private array $patterns = [];

    /**
     * @var list<string>
     */
    private array $failKeywords = [
        'gagal',
        'transaksi sebelumnya ke id pelanggan',
        'saldo tidak mencukupi',
        'tidak benar',
        'invalid',
        'reject',
        'tidak tersedia',
        'error',
        'lebih besar dari harga max',
    ];

    /**
     * @var list<string>
     */
    private array $successKeywords = [
        'alhamdulilah',
        'sukses',
        'berhasil',
    ];

    /**
     * @var list<string>
     */
    private array $pendingKeywords = [
        'mohon tunggu transaksi sedang diproses',
        'sedang diproses',
        'masih dalam proses',
    ];

    public function __construct()
    {
        $this->patterns = [
            '/^R#(\S+)\s+Saldo\s+(\w+)\s+([\d\.]+)\s+([A-Z]+\d+)\.(\d+),\s+(.*?)\s+Saldo\s+([\d\.,]+)\s*@\s*(.+)$/u' => [$this, 'parseRejectedHargaMax'],
            '/^R#(\S+)\s+(\S+)\s+(\S+)\s+(Gagal, Voucher tidak tersedia,.*?)\s+Saldo\s([\d\.,]+)\s*@\s*(.+)$/u' => [$this, 'parseNormalSixGroups'],
            '/^R#(\S+)\s+(\S+)\s+(\S+)\s+(Nomor HP tidak benar\..*?)\s+Saldo\s([\d\.,]+)\s*@\s*(.+)$/u' => [$this, 'parseNormalSixGroups'],
            '/^R#(\S+)\s+(\S+)\s+(\S+),\s+(.*?)\s+Saldo\s([\d\.,]+)\s*@\s*(.+)$/u' => [$this, 'parseNormalSixGroups'],
            '/^R#(\S+)\s+(\S+)\s+(\S+)\s+(.*?)\s+Saldo\s([\d\.,]+)\s*@\s*(.+)$/u' => [$this, 'parseNormalSixGroups'],
            '/^R#(\S+)\s+(\S+)\s+(\S+)(?:,)?\s+(.*?)\s+Saldo\s([\d\.,]+)\s*@\s*(.+)$/u' => [$this, 'parseNormalSixGroups'],
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
            $tokens = preg_split('/\s+/', $leftPart) ?: [];
            $lastToken = $tokens === [] ? null : end($tokens);

            if (is_string($lastToken) && preg_match('/\d/', $lastToken)) {
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
        $parsed = array_merge([
            'trx_id'       => null,
            'product_code' => null,
            'dest'         => null,
            'status_msg'   => null,
            'saldo'        => null,
            'datetime'     => null,
            'raw'          => trim($raw),
        ], $data);

        $parsed['transaction_status'] = $this->detectStatus($parsed['status_msg']);

        return $parsed;
    }

    private function detectStatus(?string $statusMsg): string
    {
        if (empty($statusMsg)) {
            return self::STATUS_UNKNOWN;
        }

        $normalizedStatus = mb_strtolower($statusMsg);

        if ($this->containsAnyKeyword($normalizedStatus, $this->failKeywords)) {
            return self::STATUS_FAILED;
        }

        if ($this->containsAnyKeyword($normalizedStatus, $this->pendingKeywords)) {
            return self::STATUS_PROCESS;
        }

        if ($this->containsAnyKeyword($normalizedStatus, $this->successKeywords)) {
            return self::STATUS_SUCCESS;
        }

        return self::STATUS_UNKNOWN;
    }

    /**
     * @param list<string> $keywords
     */
    private function containsAnyKeyword(string $text, array $keywords): bool
    {
        foreach ($keywords as $keyword) {
            if (str_contains($text, $keyword)) {
                return true;
            }
        }

        return false;
    }
}
