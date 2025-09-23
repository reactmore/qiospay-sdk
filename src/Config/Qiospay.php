<?php

namespace Reactmore\QiosPay\Config;

use CodeIgniter\Config\BaseConfig;

class Qiospay extends BaseConfig
{
    public string $merchantCode = '';

    public string $memberId = '';

    public string $memberPin = '';

    public string $memberPassword  = '';

    public string $apiKey = '';

    public string $qrisString = '';
}
