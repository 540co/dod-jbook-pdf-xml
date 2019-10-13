<?php

namespace Keboola\Utils;

/**
 * @param $val
 * @return int|string
 */
function returnBytes($val)
{
    $val = trim($val);
    $last = strtolower($val[strlen($val) - 1]);
    $val = substr($val, 0, -1);
    switch ($last) {
        // The 'G' modifier is available since PHP 5.1.0
        case 'g':
            $val *= 1024;
            // passthrough
        case 'm':
            $val *= 1024;
            // passthrough
        case 'k':
            $val *= 1024;
            // passthrough
    }

    return $val;
}
