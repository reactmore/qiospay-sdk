<?php

namespace Reactmore\QiosPay\Validations;

use Reactmore\SupportAdapter\Exceptions\InvalidContentType;
use Reactmore\SupportAdapter\Exceptions\MissingArguements;

/**
 * Main Validator for Data Validation
 *
 * Provides utility methods for validating API request data, including content type checks,
 * required fields validation, and nested field validation.
 */
class MainValidator
{
    /**
     * Check if the provided content is an array.
     *
     * @param mixed $content The content to check.
     *
     * @return bool True if the content is an array, false otherwise.
     */
    public static function isContentTypeArray($content)
    {
        return is_array($content);
    }

    /**
     * Get missing fields from the provided content.
     *
     * @param array $content The content to check.
     * @param array $fields  The required fields.
     *
     * @return array List of missing fields.
     */
    public static function getMissingFields($content, $fields)
    {
        return array_values(array_diff($fields, array_keys($content)));
    }

    /**
     * Validate if the content is an array.
     *
     * @param mixed $content The content to validate.
     *
     * @throws InvalidContentType If the content is not an array.
     */
    public static function validateContentType($content)
    {
        if (! self::isContentTypeArray($content)) {
            throw new InvalidContentType();
        }
    }

    /**
     * Validate required fields in the provided content.
     *
     * @param array $content The content to check.
     * @param array $fields  The required fields.
     *
     * @throws MissingArguements If any required field is missing.
     */
    public static function validateContentFields($content, $fields)
    {
        $missingFields = self::getMissingFields($content, $fields);

        if (! empty($missingFields)) {
            throw new MissingArguements('Field ' . $missingFields[0] . ' is missing');
        }
    }

    /**
     * Validate nested fields inside a given array field.
     *
     * Ensures the specified nested field exists and contains all required keys.
     *
     * @param array  $content      The main array containing the nested field.
     * @param string $field        The key of the nested array to validate.
     * @param array  $nestedFields Required keys inside the nested array.
     *
     * @throws MissingArguements If any required field is missing.
     */
    public static function validateNestedFields(array $content, string $field, array $nestedFields)
    {
        if (! isset($content[$field]) || ! is_array($content[$field])) {
            throw new MissingArguements("Field '{$field}' is missing or invalid");
        }

        $missingFields = self::getMissingFields($content[$field], $nestedFields);
        if (! empty($missingFields)) {
            throw new MissingArguements("Field '{$field}.{$missingFields[0]}' is missing");
        }
    }

    /**
     * Validate a single argument.
     *
     * Ensures the argument is not empty and is a string.
     *
     * @param mixed  $argument  The argument to validate.
     * @param string $fieldName The field name for error reporting.
     *
     * @throws MissingArguements If the argument is empty or not a string.
     */
    public static function validateSingleArgument($argument, $fieldName)
    {
        if (empty($argument) || ! is_string($argument)) {
            throw new MissingArguements("Field '{$fieldName}' is required and must be a string.");
        }
    }
}
