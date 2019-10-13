<?php

namespace Keboola\Utils;

/**
 * Returns associative array or string of data stored in $path in the $data array
 *
 * @param string $path path/to/interesting/data
 * @param array|object $data The object containing data
 * @param string $separator
 * @param bool $ignoreEmpty
 * @return mixed
 * @throws Exception/NoDataFoundException
 */
function getDataFromPath($path, $data, $separator = "/", $ignoreEmpty = true)
{
    if (isset($path) && $path !== $separator) {
        // TODO possibly add functions in the $path? Such as path/to/join(data) would do a join on that data..likely belongs to EX/(sub)Parser/TR
        $str = explode($separator, $path);
        // Step down into the $data object, iterate until the desired path is reached (if not empty)
        foreach ($str as $key) {
            if (is_object($data) && property_exists($data, $key)) {
                $data = $data->{$key};
            } elseif (is_array($data) && isset($data[$key])) {
                $data = $data[$key];
            } else {
                if ($ignoreEmpty == true) { // return if empty and ignore == true
                    $data = null;
                    break;
                } else {
                    throw new Exception\NoDataFoundException("Error parsing data. {$path} not found.");
                }
            }
        }
    }

    return $data;
}
