<?php

namespace Keboola\Utils;

/**
 * Recursively convert an associative array to object
 *
 * If the $array is not associative, an array is returned
 * @param array $array
 * @return \stdClass|array
 */
function arrayToObject(array $array)
{
    // This isn't the most efficient way!
    return json_decode(json_encode($array));
}
