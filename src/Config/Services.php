<?php

namespace Reactmore\QiosPay\Config;

use CodeIgniter\Config\BaseService;
use Reactmore\QiosPay\QiosPayProvider;

class Services extends BaseService
{
    /**
     * QiosPay service
     */
    public static function qiospay(bool $getShared = true): QiosPayProvider
    {
        if ($getShared) {
            return static::getSharedInstance('qiospay');
        }

        return new QiosPayProvider(config('Qiospay'));
    }
}
