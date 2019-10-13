<?php

namespace Keboola\Utils;

/**
 * @param $array
 * @param string $tag
 * @param string $format
 * @param null $timezone
 * @return mixed
 */
function replaceDatesInArray($array, $tag = '%%', $format = DATE_W3C, $timezone = null)
{
    array_walk_recursive($array, function (&$string, $key, $settings) {
        $string = replaceDates($string, $settings['tag'], $settings['format'], $settings['timezone']);
    }, ['tag' => $tag, 'format' => $format, 'timezone' => $timezone]);
    return $array;
}
