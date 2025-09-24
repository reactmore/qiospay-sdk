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

            $basePayload = [
                'refID'    => $refID,
                'memberID' => $this->config->memberId,
                'pin'      => $this->config->memberPin,
                'password' => $this->config->memberPassword,
            ];

            $payload = array_merge($basePayload, [
                'product' => strtolower($request['product']),
                'dest'    => $request['dest'],
            ]);

            if (! empty($request['harga_max'])) {
                $payload['harga_max'] = (int) $request['harga_max'];
            }

            $response = $this->adapter->get('api/h2h/trx', $payload);
            $body = (string) $response->getBody();

            if ($body === 'Invalid user') {
                throw new MissingArguements('Invalid user');
            }

            $parsed = [];
            if (is_string($body)) {
                $patterns = [
                    // Pola 1: ada koma setelah dest
                    '/R#(\S+)\s+(\S+)\s+(\S+),\s+(.*?)\s+Saldo\s([\d\.]+)\s@\s(.+)$/',

                    // Pola 2: dest diikuti langsung status (tanpa koma)
                    '/R#(\S+)\s+(\S+)\s+(\S+)\s+(.*?)\s+Saldo\s([\d\.]+)\s@\s(.+)$/',

                    // Pola 3: fallback umum (jaga-jaga kalau format berubah)
                    '/R#(\S+)\s+(\S+)\s+(\S+)(?:,)?\s+(.*?)\s+Saldo\s([\d\.]+)\s@\s(.+)$/',
                ];

                $parsed = ['message' => trim($body)]; // default fallback

                foreach ($patterns as $pattern) {
                    if (preg_match($pattern, $body, $matches)) {
                        $parsed = [
                            'trx_id'       => $matches[1] ?? null,
                            'product_code' => $matches[2] ?? null,
                            'dest'         => $matches[3] ?? null,
                            'status_msg'   => trim($matches[4] ?? ''),
                            'saldo'        => $matches[5] ?? null,
                            'datetime'     => $matches[6] ?? null,
                            'raw'          => trim($body),
                        ];
                        break;
                    }
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
