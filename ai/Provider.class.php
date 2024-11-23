<?php
namespace ai;

class ProviderToolDefinitionParameter {
    private string $name;
    private string $description;
    private string $type;
    private ?array $values;
    private bool $required;
    private bool $nullable;
}

class ProviderToolDefinition {
    private string $name;
    private string $description;
    private array $parameters;
}

enum MessageRole: string {
    case System = "system";
    case User = "user";
    case Ai = "ai";
    case Tool = "tool";
}

abstract class Message {
    protected static MessageRole $role;
    private string $text;

    public function __construct(string $text) {
        $this->text = $text;
    }

    public function getText(): string {
        return $this->text;
    }

    public function getRole(): MessageRole {
        return static::$role;
    }
}

class UserMessage extends Message {
    protected static MessageRole $role = MessageRole::User;
}

class SystemMessage extends Message {
    protected static MessageRole $role = MessageRole::System;
}

class AiMessage extends Message {
    protected static MessageRole $role = MessageRole::Ai;
}

class ToolMessage extends Message {
    protected static MessageRole $role = MessageRole::Tool;
}

abstract class Provider {
    protected array $messages;
    protected array $tools;

    public function __construct() {
        $this->messages = array();
        $this->tools = array();
    }

    protected abstract function generateText(): string;

    public final function ask(UserMessage $message): string {
        $this->messages[] = $message;
        return $this->generateText($message);
    }

}