<?php

namespace Reactmore\QiosPay\Services;

use GuzzleHttp\Exception\RequestException;
use Reactmore\QiosPay\Config\Qiospay;
use Reactmore\QiosPay\Services\Parsers\ResponseParser;
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
    private AdapterInterface $adapter;

    /**
     * QiosPay configuration instance.
     *
     * @var Qiospay
     */
    private Qiospay $config;

    private ResponseParser $parser;

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
        $this->parser  = new ResponseParser();

        $this->validateConfig($this->config);
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
                    $payload['password'],
                );
            }

            $response = $this->adapter->get('api/h2h/trx', $payload);
            $body     = (string) $response->getBody();

            if ($body === 'Invalid user' || $body === 'Invalid signature') {
                return ResponseFormatter::formatErrorResponse($body, 500);
            }

            $parsed = $this->parser->parse($body);

            return ResponseFormatter::formatResponse(
                json_encode($parsed, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
            );
        } catch (BaseException $e) {
            return ResponseFormatter::formatErrorResponse($e->getMessage(), $e->getCode());
        } catch (RequestException $e) {
            return $this->handleException($e);
        }
    }

    /**
     * Generate QiosPay H2H signature
     */
    public function generateQiosPaySignature(
        string $memberID,
        string $product,
        string $dest,
        string $refID,
        string $pin,
        string $password,
    ): string {
        // pastikan trim dan lowercase sesuai dok
        $product  = strtolower(trim($product));
        $dest     = trim($dest);
        $refID    = trim($refID);
        $pin      = trim($pin);
        $password = trim($password);
        $memberID = trim($memberID);

        $stringToHash = "OtomaX|{$memberID}|{$product}|{$dest}|{$refID}|{$pin}|{$password}";

        // encode base64
        return base64_encode(sha1($stringToHash));
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
