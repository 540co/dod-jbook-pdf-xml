<?php

namespace Keboola\Utils;

/**
 * @brief PHP's json_decode which throws an exception on error
 *
 * @param string $json
 * @param bool $assoc
 * @param int $depth
 * @param int $options
 * @param bool $logJson: if true, the exception data will contain the JSON
 * @param bool $lint
 * @return object|array
 * @throws \Keboola\Utils\Exception\JsonDecodeException
 */
function jsonDecode($json, $assoc = false, $depth = 512, $options = 0, $logJson = false, $lint = false)
{
    $data = json_decode($json, $assoc, $depth, $options);
    switch (json_last_error()) {
        case JSON_ERROR_NONE:
            return $data;
            break;
        case JSON_ERROR_DEPTH:
            $error = 'Maximum stack depth exceeded';
            break;
        case JSON_ERROR_STATE_MISMATCH:
            $error = 'Underflow or the modes mismatch';
            break;
        case JSON_ERROR_CTRL_CHAR:
            $error = 'Unexpected control character found';
            break;
        case JSON_ERROR_SYNTAX:
            $error = 'Syntax error, malformed JSON';
            break;
        case JSON_ERROR_UTF8:
            $error = 'Malformed UTF-8 characters, possibly incorrectly encoded';
            break;
        default:
            $error = 'Unknown error';
            break;
    }

    $e = new Exception\JsonDecodeException("JSON decode error: {$error}");

    $errData = [];
    if ($logJson) {
        $errData['json'] = $json;
    }
    if ($lint) {
        $jsonLint = new \Seld\JsonLint\JsonParser;
        $errLint = $jsonLint->lint($json);

        $errData['errDetail'] = $errLint instanceof \Seld\JsonLint\ParsingException
            ? $errLint->getMessage()
            : null;
    }

    if (!empty($errData)) {
        $e->setData($errData);
    }

    throw $e;
}
