<?php
namespace ai;
require_once(RootPath."/gemini.config.php");

// Free tier (per model)
// Input token limit per minute: 1000000
// Requests limit per day: 1500
// Requests limit per minute: 15

class Gemini {
    private static string $defaultModel = "gemini-1.5-flash";
    private static string $apiBaseUrl = "https://generativelanguage.googleapis.com/v1beta/models/";
    private string $apiKey;
    private string $model;

    public function __construct(string $model = null) {
        global $GEMINI_API_KEY;
        $this->apiKey = $GEMINI_API_KEY;
        $this->model = $model ? $model : static::$defaultModel;
    }

    public function ask(string $message): string {
        $result = Gemini::http(
            "POST",
            $this->getGenerateContentUrl(),
            array(
                "contents" => array(
                    array(
                        "role" => "user",
                        "parts" => array(
                            array(
                                "text" => $message,
                            ),
                        ),
                    ),
                ),
            ),
            true,
        );

        $answer = trim($result["candidates"][0]["content"]["parts"][0]["text"]);
        return $answer;
    }

    private function getGenerateContentUrl(): string {
        return Gemini::$apiBaseUrl.$this->model.":generateContent?key=".$this->apiKey;
    }

    private static function http(string $method, string $url, ?array $body = null, bool $json = false) {
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
}
