<?php
/* Icinga Web 2 | (c) 2017 Icinga Development Team | GPLv2+ */

namespace Icinga\Util;

use Icinga\Exception\Json\JsonDecodeException;
use Icinga\Exception\Json\JsonEncodeException;

/**
 * Wrap {@link json_encode()} and {@link json_decode()} with error handling
 */
class Json
{
    /**
     * {@link json_encode()} wrapper
     *
     * @param   mixed   $value
     * @param   int     $options
     * @param   int     $depth
     *
     * @return  string
     * @throws  JsonEncodeException
     */
    public static function encode($value, $options = 0, $depth = 512)
    {
        return static::encodeAndSanitize($value, $options, $depth, false);
    }

    /**
     * {@link json_encode()} wrapper, automatically sanitizes bad UTF-8
     *
     * @param   mixed   $value
     * @param   int     $options
     * @param   int     $depth
     *
     * @return  string
     * @throws  JsonEncodeException
     */
    public static function sanitize($value, $options = 0, $depth = 512)
    {
        return static::encodeAndSanitize($value, $options, $depth, true);
    }

    /**
     * {@link json_encode()} wrapper, sanitizes bad UTF-8
     *
     * @param   mixed   $value
     * @param   int     $options
     * @param   int     $depth
     * @param   bool    $autoSanitize   Automatically sanitize invalid UTF-8 (if any)
     *
     * @return  string
     * @throws  JsonEncodeException
     */
    protected static function encodeAndSanitize($value, $options, $depth, $autoSanitize)
    {
        if (version_compare(phpversion(), '5.5.0', '<')) {
            $encoded = json_encode($value, $options);
        } else {
            $encoded = json_encode($value, $options, $depth);
        }

        switch (json_last_error()) {
            case JSON_ERROR_NONE:
                return $encoded;

            /** @noinspection PhpMissingBreakStatementInspection */
            case JSON_ERROR_UTF8:
                if ($autoSanitize) {
                    return static::encode(static::sanitizeUtf8Recursive($value), $options, $depth);
                }
                // Fallthrough

            default:
                throw new JsonEncodeException('%s: %s', static::lastErrorMsg(), var_export($value, true));
        }
    }

    /**
     * {@link json_decode()} wrapper
     *
     * @param   string  $json
     * @param   bool    $assoc
     * @param   int     $depth
     * @param   int     $options
     *
     * @return  mixed
     * @throws  JsonDecodeException
     */
    public static function decode($json, $assoc = false, $depth = 512, $options = 0)
    {
        $decoded = json_decode($json, $assoc, $depth, $options);

        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new JsonDecodeException('%s: %s', static::lastErrorMsg(), var_export($json, true));
        }
        return $decoded;
    }

    /**
     * {@link json_last_error_msg()} replacement for PHP < 5.5.0
     *
     * @return string
     */
    protected static function lastErrorMsg()
    {
        if (version_compare(PHP_VERSION, '5.5.0', '>=')) {
            return json_last_error_msg();
        }

        // All possible error codes before PHP 5.5.0 (except JSON_ERROR_NONE)
        switch (json_last_error()) {
            case JSON_ERROR_DEPTH:
                return 'Maximum stack depth exceeded';
            case JSON_ERROR_STATE_MISMATCH:
                return 'State mismatch (invalid or malformed JSON)';
            case JSON_ERROR_CTRL_CHAR:
                return 'Control character error, possibly incorrectly encoded';
            case JSON_ERROR_SYNTAX:
                return 'Syntax error';
            case JSON_ERROR_UTF8:
                return 'Malformed UTF-8 characters, possibly incorrectly encoded';
            default:
                return 'Unknown error';
        }
    }

    /**
     * Replace bad byte sequences in UTF-8 strings inside the given JSON-encodable structure with question marks
     *
     * @param   mixed   $value
     *
     * @return  mixed
     */
    protected static function sanitizeUtf8Recursive($value)
    {
        switch (gettype($value)) {
            case 'string':
                return static::sanitizeUtf8String($value);

            case 'array':
                $sanitized = array();

                foreach ($value as $key => $val) {
                    if (is_string($key)) {
                        $key = static::sanitizeUtf8String($key);
                    }

                    $sanitized[$key] = static::sanitizeUtf8Recursive($val);
                }

                return $sanitized;

            case 'object':
                $sanitized = array();

                foreach ($value as $key => $val) {
                    if (is_string($key)) {
                        $key = static::sanitizeUtf8String($key);
                    }

                    $sanitized[$key] = static::sanitizeUtf8Recursive($val);
                }

                return (object) $sanitized;

            default:
                return $value;
        }
    }

    /**
     * Replace bad byte sequences in the given UTF-8 string with question marks
     *
     * @param   string  $string
     *
     * @return  string
     */
    protected static function sanitizeUtf8String($string)
    {
        return mb_convert_encoding($string, 'UTF-8', 'UTF-8');
    }
}
