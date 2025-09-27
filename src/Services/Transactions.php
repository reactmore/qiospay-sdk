<?php

namespace Reactmore\QiosPay\Services;

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
 * Transactions H2H Service
 *
 * Provides functionalities for managing QRIS transactions,
 * including QRIS creation, mutations retrieval, and filtering.
 */
class Transactions implements ServiceInterface
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
        if (empty($config->memberId)) {
            throw new MissingArguements('Member Id or User Id cannot be empty.');
        }

        if (empty($config->memberPin)) {
            throw new MissingArguements('Pin Code cannot be empty.');
        }

        if (empty($config->memberPassword)) {
            throw new MissingArguements('Password cannot be empty.');
        }
    }

    /**
     * Retrieve QRIS mutations from the API.
     *
     * @param array $filters Optional filters:
     *                       - refID:
     *                       - product:
     *                       - dest:
     *                       - harga_max:
     *
     * @return Response
     */
    public function h2h(array $request = [])
    {
        try {
            Validator::validateInquiryRequest($request, ['product', 'dest']);
            $refID = $request['refID'] ?? 'trx_' . time();

            $payload = [
                'product'  => $request['product'],
                'dest'     => $request['dest'],
                'refID'    => $refID,
                'memberID' => $this->config->memberId,
                'pin'      => $this->config->memberPin,
                'password' => $this->config->memberPassword,
            ];


            // optional
            if (! empty($request['harga_max'])) {
                $payload['harga_max'] = (int) $request['harga_max'];
            }

            if (! empty($request['sign'])) {
                $payload['sign'] = $this->generateQiosPaySignature(
                    $payload['memberID'],
                    $payload['product'],
                    $payload['dest'],
                    $payload['refID'],
                    $payload['pin'],
                    $payload['password']
                );
            }

            $response = $this->adapter->get('api/h2h/trx', $payload);
            $body = (string) $response->getBody();

            if ($body === 'Invalid user') {
                throw new MissingArguements('Invalid user');
            }

            $parsed = [];
            if (is_string($body)) {
                if (preg_match($this->getRejectedHargaMaxPattern(), $body, $matches)) {
                    $parsed = $this->parseRejectedHargaMax($matches, $body);
                } else {
                    // fallback ke regex lama kamu
                    $parsed = $this->parseNormalResponse($body);
                }
            }

            return ResponseFormatter::formatResponse(
                json_encode($parsed, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)
            );
        } catch (BaseException $e) {
            return ResponseFormatter::formatErrorResponse($e->getMessage(), $e->getCode());
        } catch (RequestException $e) {
            return $this->handleException($e);
        }
    }

    /**
     * Regex pattern untuk kasus Harga Max Rejected (semua provider)
     */
    private function getRejectedHargaMaxPattern(): string
    {
        return '/^R#(\S+)\s+Saldo\s+(\w+)\s+([\d\.]+)\s+([A-Z]+\d+)\.(\d+),\s+(.*?)\s+Saldo\s+([\d\.,]+)\s*@\s*(.+)$/u';
    }

    /**
     * Parse response Harga Max Rejected
     */
    private function parseRejectedHargaMax(array $matches, string $raw): array
    {
        return [
            'trx_id'       => $matches[1] ?? null,
            'product_code' => $matches[4] ?? null,  
            'dest'         => $matches[5] ?? null,   
            'status_msg'   => trim($matches[6] ?? ''), 
            'saldo'        => $matches[7] ?? null,
            'datetime'     => $matches[8] ?? null,
            'raw'          => trim($raw),
        ];
    }

    /**
     * Parse response normal (gunakan patterns lama kamu)
     */
    private function parseNormalResponse(string $body): array
    {
        $parsed = [
            'trx_id'       => null,
            'product_code' => null,
            'dest'         => null,
            'status_msg'   => null,
            'saldo'        => null,
            'datetime'     => null,
            'raw'          => trim($body),
        ];

        $patterns = [
            // 1) Normal dengan koma
            '/^R#(\S+)\s+(\S+)\s+(\S+),\s+(.*?)\s+Saldo\s([\d\.,]+)\s*@\s*(.+)$/u',
            // 2) Normal tanpa koma
            '/^R#(\S+)\s+(\S+)\s+(\S+)\s+(.*?)\s+Saldo\s([\d\.,]+)\s*@\s*(.+)$/u',
            // 3) Optional comma
            '/^R#(\S+)\s+(\S+)\s+(\S+)(?:,)?\s+(.*?)\s+Saldo\s([\d\.,]+)\s*@\s*(.+)$/u',
            // 4) Generic fallback
            '/^R#(\S+)\s+(.*?),\s+(.*?)\s+Saldo\s([\d\.,]+)\s*@\s*(.+)$/u',
        ];

        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $body, $matches)) {
                $groupCount = count($matches) - 1;

                if ($groupCount === 6) {
                    $parsed['trx_id']       = $matches[1] ?? null;
                    $parsed['product_code'] = $matches[2] ?? null;
                    $parsed['dest']         = $matches[3] ?? null;
                    $parsed['status_msg']   = trim($matches[4] ?? '');
                    $parsed['saldo']        = $matches[5] ?? null;
                    $parsed['datetime']     = $matches[6] ?? null;
                } elseif ($groupCount === 5) {
                    $parsed['trx_id']       = $matches[1] ?? null;
                    $leftPart               = trim($matches[2] ?? '');
                    $parsed['status_msg']   = trim($matches[3] ?? '');
                    $parsed['saldo']        = $matches[4] ?? null;
                    $parsed['datetime']     = $matches[5] ?? null;

                    if ($leftPart !== '') {
                        $tokens = preg_split('/\s+/', $leftPart);
                        $lastToken = end($tokens);
                        if (preg_match('/\d/', $lastToken)) {
                            array_pop($tokens);
                            $parsed['product_code'] = trim(implode(' ', $tokens)) ?: $leftPart;
                            $parsed['dest'] = $lastToken;
                        } else {
                            $parsed['product_code'] = $leftPart;
                            $parsed['dest'] = null;
                        }
                    }
                }
                break;
            }
        }

        return $parsed;
    }

    /**
     * Generate QiosPay H2H signature
     */
    function generateQiosPaySignature(
        string $memberID,
        string $product,
        string $dest,
        string $refID,
        string $pin,
        string $password
    ): string {
        // pastikan trim dan lowercase sesuai dok
        $product = strtolower(trim($product));
        $dest    = trim($dest);
        $refID   = trim($refID);
        $pin     = trim($pin);
        $password = trim($password);
        $memberID = trim($memberID);

        $stringToHash = "Ravinagc|$memberID|$product|$dest|$refID|$pin|$password";

        // sha1 raw output
        $sha1Hash = sha1($stringToHash);

        // encode base64
        return base64_encode($sha1Hash);
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
