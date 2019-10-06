<?php

namespace Keboola\Utils;

/**
 * @param $string
 * @return mixed|string
 */
function sanitizeUtf8($string)
{
    return mb_convert_encoding($string, 'UTF-8', 'UTF-8');
}
