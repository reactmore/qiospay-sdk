<?php

namespace Reactmore\QiosPay\Config;

use CodeIgniter\Config\BaseService;
use Reactmore\QiosPay\Config\Qiospay;
use Reactmore\QiosPay\QiosPayProvider;

class Services extends BaseService
{
    public static function qiospay(?Qiospay $config = null, bool $getShared = true): QiosPayProvider
    {
        if ($getShared) {
            return static::getSharedInstance('qiospay', $config);
        }

        return new QiosPayProvider($config ?? config('Qiospay'));
    }
}
