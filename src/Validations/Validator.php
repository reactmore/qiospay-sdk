<?php

namespace Reactmore\QiosPay\Validations;

use Reactmore\SupportAdapter\Exceptions\InvalidContentType;
use Reactmore\SupportAdapter\Exceptions\MissingArguements;

/**
 * Validator Class
 *
 * Provides validation methods for API requests, including checking content types,
 * required fields, and nested data structures.
 */
class Validator
{
    /**
     * Validate inquiry request data.
     *
     * Ensures the request contains the required fields and is of the correct type.
     *
     * @param mixed $request The request data.
     * @param array $fields  Required fields.
     *
     * @throws InvalidContentType
     * @throws MissingArguements
     */
    public static function validateInquiryRequest($request, $fields)
    {
        MainValidator::validateContentType($request);
        MainValidator::validateContentFields($request, $fields);
    }

    /**
     * Validate if the request is an array.
     *
     * @param mixed $request The request data.
     *
     * @throws InvalidContentType If the request is not an array.
     */
    public static function validateArrayRequest($request)
    {
        MainValidator::validateContentType($request);
    }

    public static function validateVirtualAccountRequest(array $r): void
    {
        foreach (['amount', 'bank_code', 'partner_reff', 'customer_id', 'customer_name', 'customer_phone', 'customer_email', 'url_callback'] as $f) {
            if (!isset($r[$f]) || $r[$f] === '') {
                throw new MissingArguements("Parameter '{$f}' is required.");
            }
        }
        if (!is_numeric($r['amount']) || $r['amount'] <= 0) {
            throw new MissingArguements("Parameter 'amount' must be a positive number.");
        }
        if (!\Reactmore\QiosPay\Constants\BankCodes::isValid((string) $r['bank_code'])) {
            throw new MissingArguements("Unsupported bank_code '{$r['bank_code']}'.");
        }
        if (stripos((string) $r['url_callback'], 'https://') !== 0 || !filter_var($r['url_callback'], FILTER_VALIDATE_URL)) {
            throw new MissingArguements("Parameter 'url_callback' must be a valid HTTPS URL.");
        }
    }

    /**
     * Validate payload for creating a coupon.
     *
     * Ensures the required fields exist and validates nested discount and coupon fields.
     *
     * @param array $payload The request payload.
     *
     * @throws MissingArguements If required fields are missing.
     */
    public static function validateCreateCoupon(array $payload)
    {
        MainValidator::validateContentFields($payload, ['name', 'discount']);
        MainValidator::validateNestedFields($payload, 'discount', [
            'discountType',
            'eligibleCustomerType',
            'minimumPurchase',
            'value',
            'totalCoupons',
        ]);
        if (! empty($payload['coupon'])) {
            MainValidator::validateNestedFields($payload, 'coupon', ['type']);
        }
    }

    /**
     * Validate payload for creating an installment.
     *
     * Ensures required fields exist and validates nested installment fields.
     *
     * @param array $payload The request payload.
     *
     * @throws MissingArguements If required fields are missing.
     */
    public static function validateCreateInstallment(array $payload)
    {
        MainValidator::validateContentFields($payload, ['email', 'mobile', 'name', 'amount', 'installment']);
        MainValidator::validateNestedFields($payload, 'installment', [
            'description',
            'interest',
            'tenure',
            'dueDate',
        ]);
    }

    /**
     * Validate a single argument.
     *
     * Ensures the argument is not empty and is a string.
     *
     * @param mixed  $argument  The argument to validate.
     * @param string $fieldName The field name for error reporting.
     *
     * @throws MissingArguements If the argument is empty or invalid.
     */
    public static function validateSingleArgument($argument, $fieldName)
    {
        MainValidator::validateSingleArgument($argument, $fieldName);
    }
}
