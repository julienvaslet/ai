<?php
namespace ai;
require_once(RootPath."/gemini.config.php");
require_once(RootPath."/ai/Http.class.php");
require_once(RootPath."/ai/Provider.class.php");

// Free tier (per model)
// Input token limit per minute: 1000000
// Requests limit per day: 1500
// Requests limit per minute: 15

class Gemini extends Provider {
    private static string $defaultModel = "gemini-1.5-flash";
    private static string $apiBaseUrl = "https://generativelanguage.googleapis.com/v1beta/models/";
    private string $apiKey;
    private string $model;

    public function __construct(string $model = null) {
        global $GEMINI_API_KEY;
        parent::__construct();
        $this->apiKey = $GEMINI_API_KEY;
        $this->model = $model ? $model : static::$defaultModel;
    }

    protected function getInstructions(): array {
        $instructions = implode(" ", array_map(fn (SystemMessage $message) => $message->getText(), array_filter($this->messages, fn (Message $message) => $message instanceof SystemMessage)));

        return array(
            "parts" => array(
                "text" => $instructions,
            ),
        );
    }

    protected function getMessages(): array {
        $contents = array();

        foreach ($this->messages as $message) {
            $role = static::formatRole($message->getRole());

            if (!$role) {
                continue;
            }

            $contents[] = array(
                "role" => $role,
                "parts" => array(
                    array(
                        "text" => $message->getText(),
                    ),
                ),
            );
        }

        return $contents;
    }

    protected function generateText(): string {
        $result = Http::post(
            $this->getGenerateContentUrl(),
            array(
                "system_instruction" => $this->getInstructions(),
                "contents" => $this->getMessages(),
            ),
        );

        if ($result) {
            $answer = trim($result["candidates"][0]["content"]["parts"][0]["text"]);
        }
        else {
            throw new \Exception("Service temporarily unavailable, please try again later.");
        }

        return $answer;
    }

    private function getGenerateContentUrl(): string {
        return Gemini::$apiBaseUrl.$this->model.":generateContent?key=".$this->apiKey;
    }

    protected static function formatRole(MessageRole $role): ?string {
        $mapping = array(
            MessageRole::System->value => null,
            MessageRole::User->value => "user",
            MessageRole::Ai->value => "model",
            MessageRole::Tool->value => "tool",
        );

        return $mapping[$role->value];
    }
}
