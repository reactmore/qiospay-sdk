<?php

namespace Reactmore\QiosPay;

use Reactmore\QiosPay\Config\Qiospay;
use Reactmore\QiosPay\Services\Products;
use Reactmore\QiosPay\Services\Qris;
use Reactmore\QiosPay\Services\Transactions;
use Reactmore\SupportAdapter\Adapter\Auth\None;
use Reactmore\SupportAdapter\Adapter\Guzzle;

/**
 * QiosPay Provider
 */
class QiosPayProvider
{
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

    private $baseUrl = 'https://qiospay.id/';

    public function __construct(Qiospay $config)
    {
        $this->config  = $config;
        $this->auth    = new None();
        $this->adapter = new Guzzle($this->auth, $this->baseUrl);
    }

    public function getConfig(): Qiospay
    {
        return $this->config;
    }

    /**
     * Products service
     */
    public function products(): Products
    {
        return new Products($this->adapter, $this->getConfig());
    }

    /**
     * QRIS Manager service
     */
    public function qris(): Qris
    {
        return new Qris($this->adapter, $this->getConfig());
    }

    /**
     * Transaction H2H service
     */
    public function transactions(): Transactions
    {
        return new Transactions($this->adapter, $this->getConfig());
    }
}
