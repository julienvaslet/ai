<?php
namespace api;
require_once(RootPath."/api/Endpoint.class.php");
require_once(RootPath."/ai/Gemini.class.php");

use ai\Gemini;
use ai\UserMessage;

#[Path("/chat/?")]
class ChatEndpoint extends Endpoint {
    protected $message;

    public function get() {
        $gemini = new Gemini();

        $payload = array(
            "message" => $gemini->ask(new UserMessage($this->message)),
        );
        return json_encode($payload);
    }
}

ChatEndpoint::register();
