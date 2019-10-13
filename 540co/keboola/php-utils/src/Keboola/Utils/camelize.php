<?php

namespace Keboola\Utils;

/**
 * @param $string
 * @param bool $ucfirst
 * @return mixed|string
 */
function camelize($string, $ucfirst = false)
{
    $string = str_replace(["_", "-"], " ", $string);
    $string = ucwords($string);
    $string = str_replace(" ", "", $string);
    return $ucfirst ? $string : lcfirst($string);
}
