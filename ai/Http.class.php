<?php
namespace ai;

class Http {
    private function __construct() {}

    private static function request(string $method, string $url, ?array $body = null, bool $json = true) {
        $options = array(
            "http" => array(
                "method" => strtoupper($method),
            ),
        );

        $headers = array();

        if (!is_null($body) && is_array($body)) {
            if ($json) {
                $headers["Content-Type"] = "application/json";
                $options["http"]["content"] = json_encode($body);
            }
            else {
                $headers["Content-Type"] =  "application/x-www-form-urlencoded";
                $options["http"]["content"] = http_build_query($body);
            }
        }

        if (count($headers)) {
            $options["http"]["header"] = implode("\n", array_map(fn ($header, $value) => $header.": ".$value, array_keys($headers), array_values($headers)));
        }

        $result = file_get_contents($url, false, stream_context_create($options));
        return json_decode($result, true);
    }

    public static function get(string $url, bool $json = true) {
        return static::request("GET", $url, null, $json);
    }

    public static function post(string $url, array $body, bool $json = true) {
        return static::request("POST", $url, $body, $json);
    }
}