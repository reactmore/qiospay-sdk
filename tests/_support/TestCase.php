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

        // Load .env menggunakan loader bawaan CodeIgniter 4
        $dotenv = new \CodeIgniter\Config\DotEnv(
            HOMEPATH
        );

        $dotenv->load();

        


        helper('qiospay');

        $this->config = new Qiospay();
        $this->config->apiKey       = env('Qiospay.apiKey');
        $this->config->merchantCode = env('Qiospay.merchantCode');
        $this->config->qrisString   = env('Qiospay.qrisString');
        $this->config->memberId   = env('Qiospay.memberId');
        $this->config->memberPin   = env('Qiospay.memberPin');
        $this->config->memberPassword   = env('Qiospay.memberPassword');

        $this->qiospay = new QiosPayProvider($this->config);
    }
}
