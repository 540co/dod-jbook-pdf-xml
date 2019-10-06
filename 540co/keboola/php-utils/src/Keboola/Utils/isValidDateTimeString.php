<?php

namespace Keboola\Utils;

/**
 * Check if a string is a valid date(time)
 *
 * @link http://www.pontikis.net/tip/?id=21
 *
 * @param string $dateStr
 * @param string $dateFormat
 * @param string $timezone (If timezone is invalid, php will throw an exception)
 * @return bool
 */
function isValidDateTimeString($dateStr, $dateFormat, $timezone = null)
{
    if ($timezone) {
        $date = \DateTime::createFromFormat(
            $dateFormat,
            $dateStr,
            new \DateTimeZone($timezone)
        );
    } else {
        $date = \DateTime::createFromFormat($dateFormat, $dateStr);
    }

    return $date && \DateTime::getLastErrors()["warning_count"] == 0 && \DateTime::getLastErrors()["error_count"] == 0;
}
