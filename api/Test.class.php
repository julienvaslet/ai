<?php
namespace api;
require_once(RootPath."/api/Endpoint.class.php");
require_once(RootPath."/ai/Agent.class.php");

use ai;
use api\Endpoint;

#[Path("/test/?")]
class TestEndpoint extends Endpoint {
    protected $message;

    public function get() {
        $ai = new WordCountingAgent();

        $payload = array(
            "message" => $ai->ask($this->message),
        );
        return json_encode($payload);
    }
}

TestEndpoint::register();


class WordCountingAgent extends ai\Agent {
    public function __construct() {
        parent::__construct("https://ai.vaslet.ca", "gemini-1.5-flash");
    }

    #[ai\Tool]
    /**
     * Counts the words in the specified message.
     * @param string $message the message.
     */
    private function countWords(string $message): int {
        return count(explode(" ", $message));
    }

    public function ask(string $message): string {
        $answer = parent::ask("not computed");
        return "There are ".$answer." words in: ". $message;
    }
}
