<?php

namespace Reactmore\QiosPay\Services;

use Endroid\QrCode\QrCode;
use Endroid\QrCode\Writer\PngWriter;
use GuzzleHttp\Exception\RequestException;
use Reactmore\QiosPay\Config\Qiospay;
use Reactmore\QiosPay\Services\Traits\BodyAccessorTrait;
use Reactmore\QiosPay\Validations\Validator;
use Reactmore\SupportAdapter\Adapter\AdapterInterface;
use Reactmore\SupportAdapter\Adapter\Formatter\Response;
use Reactmore\SupportAdapter\Adapter\Formatter\ResponseFormatter;
use Reactmore\SupportAdapter\Exceptions\BaseException;
use Reactmore\SupportAdapter\Exceptions\MissingArguements;

/**
 * Qris Service
 *
 * Provides functionalities for managing QRIS transactions,
 * including QRIS creation, mutations retrieval, and filtering.
 */
class Qris implements ServiceInterface
{
    use BodyAccessorTrait;

    /**
     * HTTP adapter for API communication.
     *
     * @var AdapterInterface
     */
    private $adapter;

    /**
     * QiosPay configuration instance.
     *
     * @var Qiospay
     */
    private $config;

    /**
     * Qris constructor.
     *
     * Initializes the Qris service with the provided HTTP adapter
     * and configuration.
     *
     * @param AdapterInterface $adapter HTTP adapter instance.
     * @param Qiospay|null     $config  Optional configuration instance.
     *
     * @throws MissingArguements
     */
    public function __construct(AdapterInterface $adapter, ?Qiospay $config = null)
    {
        $this->config  = $config ?? new Qiospay();
        $this->adapter = $adapter;

        $this->validateConfig($config);
    }

    /**
     * Validate the QiosPay configuration.
     *
     * @param Qiospay $config QiosPay configuration instance.
     *
     * @throws MissingArguements If required configuration values are missing.
     */
    protected function validateConfig(Qiospay $config): void
    {
        if (empty($config->apiKey)) {
            throw new MissingArguements('API Key cannot be empty.');
        }

        if (empty($config->merchantCode)) {
            throw new MissingArguements('Merchant Code cannot be empty.');
        }

        if (empty($config->qrisString)) {
            throw new MissingArguements('QRIS String cannot be empty.');
        }
    }

    /**
     * Retrieve QRIS mutations from the API.
     *
     * @param array $filters Optional filters:
     *                       - type: 'CR' or 'DB'
     *                       - date: 'dd-mm-yyyy'
     *                       - amount: float
     *
     * @return Response
     */
    public function getMutation(array $filters = [])
    {
        try {
            Validator::validateArrayRequest($filters);
            $request  = $this->adapter->get("api/mutasi/qris/{$this->config->merchantCode}/{$this->config->apiKey}");
            $response = json_decode($request->getBody()->getContents(), true);

            if (($response['status'] ?? null) === 'success') {
                $data = $response['data'] ?? [];
                if (! empty($filters)) {
                    $data = $this->filterMutation($response, $filters['type'] ?? null, $filters['date'] ?? null, $filters['amount'] ?? null);
                }

                return ResponseFormatter::formatResponse(json_encode($data));
            }

            return ResponseFormatter::formatErrorResponse('Invalid response from API');
        } catch (BaseException $e) {
            return ResponseFormatter::formatErrorResponse($e->getMessage(), $e->getCode());
        } catch (RequestException $e) {
            return $this->handleException($e);
        }
    }

    /**
     * Filter QRIS mutation data by type, date, and amount.
     *
     * @param array       $mutationData Full mutation response from API.
     * @param string|null $typeFilter   Optional type filter ('CR' or 'DB').
     * @param string|null $dateFilter   Optional date filter (format: 'YYYY-MM-DD').
     * @param float|null  $amountFilter Optional amount filter.
     *
     * @return array Filtered mutation data.
     */
    private function filterMutation(array $mutationData, ?string $typeFilter = null, ?string $dateFilter = null, ?float $amountFilter = null): array
    {
        if (! isset($mutationData['data']) || ! is_array($mutationData['data'])) {
            return [];
        }

        return array_values(array_filter($mutationData['data'], static function ($row) use ($typeFilter, $dateFilter, $amountFilter) {
            if ($typeFilter !== null && strtoupper($row['type']) !== $typeFilter) {
                return false;
            }

            if ($dateFilter && ! str_starts_with($row['date'], $dateFilter)) {
                return false;
            }

            return ! ($amountFilter !== null && (float) $row['amount'] !== (float) $amountFilter);
        }));
    }

    /**
     * Generate a dynamic QRIS code based on static QRIS with
     * optional transaction amount and service fee.
     *
     * Example usage:
     * ```php
     * $response = $qiosPay->qris()->createQris([
     *     'amount'      => 15000,
     *     'service_fee' => true,
     *     'fee_type'    => 'persen',
     *     'fee_value'   => 1.0,
     *     'path'        => WRITEPATH . '/uploads/QRIS',
     * ]);
     * ```
     *
     * @param array $params {
     *                      Optional parameters.
     *
     * @type int|null $amount      Transaction amount.
     * @type bool     $service_fee Enable service fee.
     * @type string   $fee_type    Fee type: 'persen' or 'rupiah'.
     *
     * @var float|int   $fee_value   Fee value.
     * @var string|null $path        Directory path to save the QR image.
     *                  }
     *
     * @return Response
     */
    public function createQris(array $params = [])
    {
        $amount      = $params['amount'] ?? null;
        $service_fee = $params['service_fee'] ?? false;
        $feeType     = $params['fee_type'] ?? 'persen';
        $feeValue    = $params['fee_value'] ?? 0.7;
        $path        = $params['path'] ?? null;

        $tax       = '';
        $qrPayload = $this->config->qrisString;

        if ($amount) {
            // Hitung pajak/biaya layanan
            if ($service_fee) {
                $feeValue = trim((string) $feeValue);
                $feeCode  = ($feeType === 'rupiah') ? '55020256' : '55020357';
                $tax      = $feeCode . sprintf('%02d', strlen($feeValue)) . $feeValue;
            }

            // Ubah QR static ke dynamic dan sisipkan nominal
            $qrBase  = substr($this->config->qrisString, 0, -4); // Hilangkan CRC lama
            $qrBase  = str_replace('010211', '010212', $qrBase);
            $qrParts = explode('5802ID', $qrBase);

            $nominal = '54' . sprintf('%02d', strlen($amount)) . $amount;
            $nominal .= $tax ? $tax . '5802ID' : '5802ID';

            $qrFinal = trim($qrParts[0]) . $nominal . trim($qrParts[1]);

            // Tambahkan CRC baru
            $qrFinal .= $this->calculateCRC16($qrFinal);
            $qrPayload = $qrFinal;
        }

        // Generate PNG QR code base64
        $qrCode = new QrCode($qrPayload);
        $writer = new PngWriter();
        $image  = $writer->write($qrCode);

        $base64 = 'data:image/png;base64,' . base64_encode($image->getString());

        $result = [
            'qris_string' => $qrPayload,
            'qris_image'  => $base64,
        ];

        // Simpan ke file jika $path diberikan
        if ($path !== null) {
            if (! is_dir($path)) {
                mkdir($path, 0777, true);
            }

            $filename = 'qris_' . time() . '.png';
            $filePath = rtrim($path, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . $filename;

            file_put_contents($filePath, $image->getString());

            $result['qris_path'] = $filePath;
        }

        return ResponseFormatter::formatResponse(json_encode($result));
    }

    /**
     * Calculate CRC16-CCITT checksum for validating QR payload.
     *
     * @param string $str Input string.
     *
     * @return string CRC16 checksum.
     */
    private function calculateCRC16($str)
    {
        $crc = 0xFFFF;

        for ($c = 0, $len = strlen($str); $c < $len; $c++) {
            $crc ^= ord($str[$c]) << 8;

            for ($i = 0; $i < 8; $i++) {
                $crc = ($crc & 0x8000) ? ($crc << 1) ^ 0x1021 : ($crc << 1);
                $crc &= 0xFFFF; // Pastikan 16-bit
            }
        }

        return strtoupper(str_pad(dechex($crc), 4, '0', STR_PAD_LEFT));
    }

    /**
     * Handle API request exceptions.
     *
     * Processes and formats exceptions that occur during API requests,
     * returning a structured error response.
     *
     * @param RequestException $e The caught exception.
     *
     * @return array Formatted error response with status code.
     */
    private function handleException(RequestException $e)
    {
        $response     = $e->getResponse();
        $statusCode   = $response ? $response->getStatusCode() : 500;
        $responseBody = $response ? $response->getBody()->getContents() : null;

        if ($responseBody) {
            $errorData    = json_decode($responseBody, true);
            $errorMessage = $errorData['messages'] ?? 'An error occurred';

            return ResponseFormatter::formatErrorResponse($errorMessage, $statusCode);
        }

        return ResponseFormatter::formatErrorResponse($e->getMessage(), $statusCode);
    }
}
