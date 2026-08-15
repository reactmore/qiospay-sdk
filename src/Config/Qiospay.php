<?php

namespace Reactmore\QiosPay\Config;

use CodeIgniter\Config\BaseConfig;

class Qiospay extends BaseConfig
{
    /**
     * QiosPay merchant credentials.
     */
    public string $merchantCode   = '';
    public string $memberId       = '';
    public string $memberPin      = '';
    public string $memberPassword = '';
    public string $apiKey         = '';

    /**
     * Static QRIS merchant payload.
     */
    public string $qrisString = '';

    /**
     * Default QRIS service fee.
     */
    public string $defaultFeeType = 'persen';
    public float $defaultFeeValue = 0.7;

    /**
     * QRIS service fee codes.
     */
    public string $feeCodePersen = '55020357';
    public string $feeCodeRupiah = '55020256';

    /**
     * Default directory for generated QRIS images.
     */
    public ?string $qrisImagePath = null;
}
