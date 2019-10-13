<?php

namespace Keboola\Utils;

use Keboola\Utils\Sanitizer\ColumnNameSanitizer;

/**
 * https://github.com/nette/nette/blob/master/Nette/Utils/Strings.php#L167
 * Converts to ASCII.
 * @param  string  UTF-8 encoding
 * @return string  ASCII
 */
function toAscii($s)
{
    return ColumnNameSanitizer::toAscii($s);
}
