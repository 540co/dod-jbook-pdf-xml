<?php

namespace Keboola\Utils;

/**
 * Convert an object to associative array
 *
 * @param object $object
 * @return array
 */
function objectToArray($object)
{
    $data = (array) $object;
    foreach ($data as $key => $value) {
        if (is_object($value) || is_array($value)) {
            $data[$key] = objectToArray($value);
        }
    }
    return $data;
}
