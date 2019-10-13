<?php

namespace Keboola\Utils;

/**
 * @brief Inject query into an URL string
 *
 * @param string $url
 * @param array $query Associative array containing query
 * @return string Altered URL
 */
function buildUrl($url, array $query = null)
{
    if (!empty($query)) {
        // Cleanup the input array to prevent empty Keys
        foreach ($query as $k => $v) {
            if (empty($k)) {
                unset($query[$k]);
            }
        }

        # Parse the url to get a query string
        $parsed = parse_url($url);

        # If a query string is set, parse it into an associative array (..&key=value => array("key" => "value"))
        if (isset($parsed["query"]) && strlen($parsed["query"]) > 0) {
            $newQuery = array();
            $pairs = explode("&", $parsed["query"]);
            foreach ($pairs as $pair) {
                list($key, $val) = explode("=", $pair, 2);
                $newQuery[$key] = urldecode($val);
            }
            # Add/Replace parameters from $query
            $newQuery = array_replace($newQuery, $query);
        } else {
            $newQuery = $query;
        }
        $parsed["query"] = http_build_query($newQuery);

        # Rebuild the query back
        $url = httpBuildUrl($parsed);
    }

    return $url;
}
