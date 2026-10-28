<?php

namespace Reactmore\QiosPay\Constants;

final class BankCodes
{
    public const LIST = [
        'BRI'      => '002',
        'BNI'      => '009',
        'Mandiri'  => '008',
        'BNC'      => '490',
        'CIMB'     => '022',
        'Maybank'  => '016',
        'Permata'  => '013',
        'BSI'      => '451',
        'OCBC'     => '028',
        'Danamon'  => '011',
        'Muamalat' => '147',
    ];

    public static function isValid(string $code): bool
    {
        return in_array($code, self::LIST, true);
    }
}
