<?php

namespace Keboola\Utils;

/**
 * Recursively scans $object for non-empty objects
 * Returns true if the object contains no scalar nor array
 * @param \stdClass $object
 * @return bool
 */
function isEmptyObject(\stdClass $object)
{
    $vars = get_object_vars($object);
    if ($vars == []) {
        return true;
    } else {
        foreach ($vars as $var) {
            if (!is_object($var)) {
                return false;
            } else {
                return isEmptyObject((object) $var);
            }
        }
    }
}
