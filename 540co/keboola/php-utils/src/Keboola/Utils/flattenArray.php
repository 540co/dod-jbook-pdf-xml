<?php

namespace Keboola\Utils;

/**
 * Take a multidimensional array and return a singledimensional one
 * Array keys are concatenated by "."
 * @param array $array
 * @param string $prefix Prefix the array key
 * @param string $glue
 * @return array
 */
function flattenArray(array $array, $prefix = "", $glue = '.')
{
    $result = [];
    foreach ($array as $key => $value) {
        if (is_array($value)) {
            $result = array_merge($result, flattenArray($value, $prefix . $key . $glue));
        } else {
            $result[$prefix . $key] = $value;
        }
    }
    return $result;
}
