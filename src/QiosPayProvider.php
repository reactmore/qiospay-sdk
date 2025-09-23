<?php

namespace Reactmore\QiosPay;

use Reactmore\QiosPay\Config\Qiospay;
use Reactmore\SupportAdapter\Adapter\Auth\None;
use Reactmore\SupportAdapter\Adapter\Guzzle;
use Reactmore\QiosPay\Services\Products;

/**
 * QiosPay Provider
 *
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


    private $baseUrl = "https://qiospay.id/";

    public function __construct(Qiospay $config)
    {
        $this->config = $config;
        $this->auth = new None();
        $this->adapter = new Guzzle($this->auth, $this->baseUrl);
    }

    public function getConfig(): Qiospay
    {
        return $this->config;
    }

    /**
     * Products service
     * 
     * @return Products
     */
    public function products(): Products
    {
        return new Products($this->adapter, $this->getConfig());
    }
}
