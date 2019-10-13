<?php

namespace Keboola\Utils;

/**
 * @param $string
 * @param string $tag
 * @param string $format
 * @param null $timezone
 * @return mixed
 */
function replaceDates($string, $tag = '%%', $format = DATE_W3C, $timezone = null)
{
    return preg_replace_callback(
        '/'.preg_quote($tag).'(.*?)'.preg_quote($tag).'/',
        function ($matches) use ($format, $timezone) {
            return formatDateTime($matches[1], $format, $timezone);
        },
        $string
    );
}
