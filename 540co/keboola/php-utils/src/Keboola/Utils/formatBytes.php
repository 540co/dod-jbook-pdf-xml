<?php

namespace Keboola\Utils;

/**
 * @param $bytes
 * @param int $precision
 * @return string
 */
function formatBytes($bytes, $precision = 2)
{
    $bytes = round($bytes);
    $units = ['B', 'kB', 'MB', 'GB', 'TB', 'PB'];
    foreach ($units as $unit) {
        if (abs($bytes) < 1024 || $unit === end($units)) {
            break;
        }
        $bytes /= 1024;
    }
    $precision = round($precision);

    return round($bytes, $precision < 0 ? 0 : $precision) . ' ' . $unit;
}
