<?php

namespace Keboola\Utils;

/**
 * @brief PECL http_build_query() replacement.
 * Takes an array containing information about a parsed URL and rebuilds the URL from it.
 * See http://php.net/manual/en/function.http-build-url.php
 *
 * @param array parse_url Array containing the parsed URL (i.e. result of http://ca1.php.net/manual/en/function.parse-url.php)
 * @return string
 **/
function httpBuildUrl(array $parse_url)
{
    // Skip if the URL is relative
    if (!empty($parse_url["scheme"]) && !empty($parse_url["host"])) {
        // scheme - e.g. http
        $url = isset($parse_url["scheme"]) ? $parse_url["scheme"] : "http";
        $url .= "://";
        // user
        if (isset($parse_url["user"])) {
            $url .= $parse_url["user"];
            // pass
            $url .= isset($parse_url["pass"]) ? ":{$parse_url["pass"]}" : "";
            $url .= "@";
        }
        // host
        $url .= isset($parse_url["host"]) ? $parse_url["host"] : "";
        // port
        $url .= isset($parse_url["port"]) ? ":{$parse_url["port"]}" : "";
    } else {
        $url = "";
    }

    // path
    $url .= isset($parse_url["path"]) ? $parse_url["path"] : "";
    // query - after the question mark ?
    $url .= isset($parse_url["query"]) ? "?{$parse_url["query"]}" : "";
    // fragment - after the hashmark #
    $url .= isset($parse_url["fragment"]) ? "#{$parse_url["fragment"]}" : "";
    return $url;
}
