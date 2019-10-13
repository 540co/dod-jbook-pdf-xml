<?php
namespace Keboola\Utils;

/**
 * Strip UTF16 surrogates
 *
 * @param $string
 * @return mixed
 */
function stripInvalidUtf16($string)
{
    $regex = "/(\\\\ud83c\\\\u[0-9a-f]{4})|(\\\\ud83d\\\\u[0-9a-f]{4})|(\\\\u[0-9a-f]{4})/i";

    return preg_replace($regex, '', $string);
}
