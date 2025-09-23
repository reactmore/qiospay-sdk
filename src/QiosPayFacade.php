<?php

namespace Reactmore\QiosPay;

use InvalidArgumentException;
use Reactmore\QiosPay\Config\Qiospay;

class QiosPayFacade
{
    protected QiosPayProvider $provider;

    public function __construct(array|Qiospay $config)
    {
        if (is_array($config)) {
            $qiosConfig = new Qiospay();

            foreach ($config as $key => $value) {
                if (property_exists($qiosConfig, $key)) {
                    $qiosConfig->{$key} = $value;
                }
            }
        } elseif ($config instanceof Qiospay) {
            $qiosConfig = $config;
        } else {
            throw new InvalidArgumentException('Config harus berupa array atau instance QiospayConfig.');
        }

        $this->provider = new QiosPayProvider($qiosConfig);
    }

    public function __call(string $method, array $args)
    {
        return $this->provider->{$method}(...$args);
    }
}
