<?php

namespace Tests\Support;

use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\DatabaseTestTrait;
use Reactmore\QiosPay\Config\Qiospay;
use Reactmore\QiosPay\QiosPayProvider;

/**
 * @internal
 */
abstract class TestCase extends CIUnitTestCase
{
    use DatabaseTestTrait;

    /**
     * @var bool
     */
    protected $refresh = true;

    /**
     * @var array|string|null
     */
    protected $namespace = 'Reactmore\QiosPay';

    protected Qiospay $config;

    /**
     * Tripay instance preconfigured for testing
     */
    protected \Reactmore\QiosPay\QiosPayProvider $qiospay;

    protected function setUp(): void
    {
        parent::setUp();

        helper('qiospay');

        $this->config = new Qiospay();
        $this->config->apiKey       = 'dummy_api_key';
        $this->config->merchantCode = 'dummy_merchant_code';
        $this->config->qrisString   = 'dummy_merchant_qris_string';
        $this->config->memberId   = 'dummy_memberId';
        $this->config->memberPin   = 'dummy_memberPin';
        $this->config->memberPassword   = 'dummy_memberPassword';

        $this->qiospay = new QiosPayProvider($this->config);
    }
}
