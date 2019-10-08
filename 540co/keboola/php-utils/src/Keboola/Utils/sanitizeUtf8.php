<?php

namespace Keboola\Utils;

/**
 *
 * taken from https://api.nette.org/2.4/source-Utils.Strings.php.html#34-43
 *
 * @param $string
 * @return mixed|string
 */
function sanitizeUtf8($string)
{
    return htmlspecialchars_decode(htmlspecialchars($string, ENT_NOQUOTES | ENT_IGNORE, 'UTF-8'), ENT_NOQUOTES);
}
