<?php

namespace Keboola\Utils;

use Keboola\Utils\Sanitizer\ColumnNameSanitizer;

/**
 *https://github.com/nette/utils/blob/v2.5/src/Utils/Strings.php#L199
 * Converts to web safe characters [a-z0-9-] text.
 * @param  string  UTF-8 encoding
 * @param  string  allowed characters
 * @param  bool
 * @return string
 */
function sanitizeColumnName($s, $charlist = '_', $lower = false)
{
    return ColumnNameSanitizer::sanitize($s, $charlist, $lower);
}
