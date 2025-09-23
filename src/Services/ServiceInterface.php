<?php

namespace Reactmore\QiosPay\Services;

use Reactmore\QiosPay\Config\Qiospay;
use Reactmore\SupportAdapter\Adapter\AdapterInterface;

/**
 * Interface ServiceInterface
 * @package Reactmore\QiosPay\Services
 */
interface ServiceInterface
{
    /**
     * ServiceInterface constructor.
     * @param AdapterInterface $adapter
     */
    public function __construct(AdapterInterface $adapter, ?Qiospay $config = null);
}
