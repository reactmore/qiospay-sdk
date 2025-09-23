<?php

namespace Reactmore\QiosPay\Services;

use Reactmore\QiosPay\Config\Qiospay;
use Reactmore\SupportAdapter\Adapter\AdapterInterface;

/**
 * Interface ServiceInterface
 */
interface ServiceInterface
{
    /**
     * ServiceInterface constructor.
     */
    public function __construct(AdapterInterface $adapter, ?Qiospay $config = null);
}
