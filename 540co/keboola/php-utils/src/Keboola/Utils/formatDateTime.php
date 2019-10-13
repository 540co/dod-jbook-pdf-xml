<?php

namespace Keboola\Utils;

/**
 * @param $dateTime
 * @param string $format
 * @param null $timezone
 * @return string
 */
function formatDateTime($dateTime, $format = DATE_W3C, $timezone = null)
{
    $dtzObj = $timezone ? new \DateTimeZone($timezone) : null;
    $dtObj = new \DateTime($dateTime, $dtzObj);
    return $dtObj->format($format);
}
