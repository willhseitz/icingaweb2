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
        $encoded = json_encode($value, $options, $depth);

        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new JsonEncodeException('%s: %s', json_last_error_msg(), var_export($value, true));
        }

        return $encoded;
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
            throw new JsonDecodeException('%s: %s', json_last_error_msg(), var_export($json, true));
        }

        return $decoded;
    }
}
