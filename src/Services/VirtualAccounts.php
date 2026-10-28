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
 * Virtual Account Service
 *
 * Provides functionalities for managing Virtual Accounts,
 * including Create.
 */
class VirtualAccounts implements ServiceInterface
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
     * Generate Virtual Account from the API.
     *
     * @return Response
     */
    public function create(array $request = [])
    {
        try {
            Validator::validateInquiryRequest($request, $request);

            $payload = [
                'username'       => $this->config->memberId,
                'pin'            => $this->config->apiKey,
                'amount'         => $request['amount'],
                'bank_code'      => $request['bank_code'],
                'partner_reff'   => $request['partner_reff'],
                'customer_id'    => $request['customer_id'],
                'customer_name'  => $request['customer_name'],
                'customer_phone' => $request['customer_phone'],
                'customer_email' => $request['customer_email'],
                'url_callback'   => $request['url_callback'],
            ];

            $response = $this->adapter->post('api/create/va', ['json' => $payload]);
            $body     = (string) $response->getBody();
            $data     = json_decode($body, true);

            if (!is_array($data)) {
                return ResponseFormatter::formatErrorResponse($body ?: 'Invalid response', 500);
            }

            if (($data['status'] ?? '') === 'error') {
                return ResponseFormatter::formatErrorResponse($data['response'] ?? 'Terjadi kesalahan', 400);
            }

            return ResponseFormatter::formatResponse($body);
        } catch (BaseException $e) {
            return ResponseFormatter::formatErrorResponse($e->getMessage(), $e->getCode());
        } catch (RequestException $e) {
            return $this->handleException($e);
        }
    }

    public function callback($request)
    {
        try {
            if (is_object($request) && method_exists($request, 'getBody')) {
                $request = (string) $request->getBody();
            }
            $data = is_string($request) ? json_decode($request, true) : $request;

            if (!is_array($data)) {
                throw new MissingArguements('Invalid callback payload.');
            }

            $fields = ['status', 'amount', 'va_code', 'va_number', 'customer_name', 'partner_reff', 'serialnumber'];
            foreach ($fields as $f) {
                if (!array_key_exists($f, $data)) {
                    throw new MissingArguements("Callback field '{$f}' is missing.");
                }
            }

            return ResponseFormatter::formatResponse(
                json_encode(array_intersect_key($data, array_flip($fields)), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)
            );
        } catch (BaseException $e) {
            return ResponseFormatter::formatErrorResponse($e->getMessage(), 400);
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
