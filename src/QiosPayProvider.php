<?php

namespace Reactmore\QiosPay;

use Reactmore\QiosPay\Config\Qiospay;
use Reactmore\SupportAdapter\Adapter\Auth\None;
use Reactmore\SupportAdapter\Adapter\Guzzle;
use Reactmore\SupportAdapter\Exceptions\MissingArguements;

class QiosPayProvider
{
    use QiosPayTraits;

    protected Qiospay $config;

    /**
     * HTTP adapter for API requests.
     *
     * @var Guzzle|null
     */
    private $adapter;

    /**
     * API authentication token.
     *
     * @var None|null
     */
    private $auth;

    public function __construct(Qiospay $config)
    {
        $this->validateConfig($config);

        $this->config = $config;
        $this->adapter = new Guzzle(new None(), 'https://qiospay.id/');
    }

    protected function validateConfig(Qiospay $config): void
    {
        if (empty($config->apiKey)) {
            throw new MissingArguements("API Key tidak boleh kosong.");
        }

        if (empty($config->merchantCode)) {
            throw new MissingArguements("Merchant Code tidak boleh kosong.");
        }
    }

    /**
     * Handles dynamic method calls for accessing API services.
     *
     * @param string $name The name of the service (e.g., 'qris', 'h2h').
     * @param array $arguments Arguments passed to the service constructor.
     * @return ServiceInterface Returns an instance of the requested service.
     *
     * @throws \BadMethodCallException If the requested service does not exist.
     */
    public function __call($name, $arguments)
    {
        $className = "\\Reactmore\QiosPay\\Services\\" . ucfirst($name);

        if (class_exists($className)) {
            return new $className($this->adapter);
        }

        throw new \BadMethodCallException("Service {$name} not found in this sdk.");
    }
}
