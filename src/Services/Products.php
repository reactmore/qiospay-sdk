<?php

namespace Reactmore\QiosPay\Services;

use Reactmore\SupportAdapter\Adapter\AdapterInterface;
use Reactmore\SupportAdapter\Adapter\Formatter\ResponseFormatter;
use Reactmore\QiosPay\Services\ServiceInterface;
use Reactmore\QiosPay\Services\Traits\BodyAccessorTrait;
use Reactmore\QiosPay\Validations\Validator;
use GuzzleHttp\Exception\RequestException;
use Reactmore\SupportAdapter\Exceptions\BaseException;

/**
 * Customer Service for Mayar Headless API V1
 *
 * Provides functionalities for managing customer data, including retrieval,
 * creation, updating, and generating magic links.
 *
 * @package Reactmore\QiosPay\Services\Products
 */
class Products implements ServiceInterface
{
    use BodyAccessorTrait;

    /**
     * HTTP adapter for API communication.
     *
     * @var AdapterInterface
     */
    private $adapter;

    /**
     * Customer constructor.
     *
     * Initializes the Customer service with the provided HTTP adapter.
     *
     * @param AdapterInterface $adapter HTTP adapter instance.
     */
    public function __construct(AdapterInterface $adapter)
    {
        $this->adapter = $adapter;
    }

    /**
     * Retrieve products for a single page with optional filters.
     *
     * @param array $filters Optional key-value filters to apply to data.
     * @param int $page Page number to fetch.
     * @param callable|null $dataFilter Optional callback to filter/transform API data.
     * 
     * Example of $dataFilter callback:
     * ```
     * $dataFilter = function(array $products) {
     *     return array_filter($products, fn($item) => $item['price'] > 100);
     * };
     * $response = $this->getProduct([], 1, $dataFilter);
     * ```
     * 
     * @return \Reactmore\SupportAdapter\Adapter\Formatter\Response
     */
    public function getProduct(array $filters = [], int $page = 1, ?callable $dataFilter = null)
    {
        try {
            Validator::validateArrayRequest($filters);

            $request = $this->adapter->get("admin/modules/mapping/harga/{$page}/reseller");
            $responseData = json_decode($request->getBody()->getContents(), true);

            // Apply local filters if provided
            if (!empty($filters)) {
                $responseData = array_values(array_filter($responseData, function ($item) use ($filters) {
                    foreach ($filters as $key => $value) {
                        if (!isset($item[$key]) || (string)$item[$key] !== (string)$value) {
                            return false;
                        }
                    }
                    return true;
                }));
            }

            return ResponseFormatter::formatResponse(
                json_encode($responseData),
                message: null,
                dataFilter: $dataFilter
            );
        } catch (BaseException $e) {
            return ResponseFormatter::formatErrorResponse($e->getMessage(), $e->getCode());
        } catch (RequestException $e) {
            return $this->handleException($e);
        }
    }

    /**
     * Retrieve all products across multiple pages with optional filters.
     *
     * @param array $filters Key-value filters to apply on each page.
     * @param int $maxPage Maximum number of pages to fetch.
     * @param callable|null $dataFilter Optional callback to filter/transform data.
     * 
     * ```
     * $dataFilter = function(array $products) {
     *     return array_filter($products, fn($item) => $item['stock'] > 0);
     * };
     * $response = $productsService->getAll([], 5, $dataFilter);
     * ```
     * 
     * @return \Reactmore\SupportAdapter\Adapter\Formatter\Response
     */
    public function getAll(array $filters = [], int $maxPage = 10, ?callable $dataFilter = null)
    {
        try {
            $allProducts = [];

            for ($page = 1; $page <= $maxPage; $page++) {
                $response = $this->getProduct($filters, $page, $dataFilter);

                $pageData = $response->getData();

                if (!is_array($pageData) || empty($pageData)) {
                    break; // stop if no data or invalid format
                }

                $allProducts = array_merge($allProducts, $pageData);
            }

            return ResponseFormatter::formatResponse(
                json_encode($allProducts),
                message: null,
                dataFilter: $dataFilter
            );
        } catch (BaseException $e) {
            return ResponseFormatter::formatErrorResponse($e->getMessage(), $e->getCode());
        } catch (RequestException $e) {
            return $this->handleException($e);
        }
    }

    /**
     * Retrieve unique product categories across all pages.
     *
     * @param array $filters Key-value filters to apply on each page.
     * @param int $maxPage Maximum number of pages to fetch.
     * @param callable|null $dataFilter Optional callback to filter/transform data.
     * @return \Reactmore\SupportAdapter\Adapter\Formatter\Response
     */
    public function getCategories(array $filters = [], int $maxPage = 10, ?callable $dataFilter = null)
    {
        $allProductsResponse = $this->getAll($filters, $maxPage, $dataFilter);
        $allProducts = $allProductsResponse->getData();

        if (empty($allProducts) || !is_array($allProducts)) {
            return ResponseFormatter::formatResponse(json_encode([]));
        }

        $categories = array_map(fn($item) => $item['produk'] ?? null, $allProducts);
        $categories = array_values(array_unique(array_filter($categories)));

        return ResponseFormatter::formatResponse(json_encode($categories));
    }



    /**
     * Handle API request exceptions.
     *
     * Processes and formats exceptions that occur during API requests,
     * returning a structured error response.
     *
     * @param RequestException $e The caught exception.
     * @return array Formatted error response with status code.
     */
    private function handleException(RequestException $e)
    {
        $response = $e->getResponse();
        $statusCode = $response ? $response->getStatusCode() : 500;
        $responseBody = $response ? $response->getBody()->getContents() : null;

        if ($responseBody) {
            $errorData = json_decode($responseBody, true);
            $errorMessage = $errorData['messages'] ?? 'An error occurred';
            return ResponseFormatter::formatErrorResponse($errorMessage, $statusCode);
        }

        return ResponseFormatter::formatErrorResponse($e->getMessage(), $statusCode);
    }
}
