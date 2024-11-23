<?php

namespace ai;

#[\Attribute(\Attribute::TARGET_METHOD)]
class Tool {}

class ToolDefinitionParameter {
    private string $name;
    private string $description;
    private string $type;
    private ?array $values;
    private bool $required;
    private bool $nullable;

    public function __construct(\ReflectionParameter $parameter, array $descriptions) {
        $this->name = $parameter->getName();
        $description = array_key_exists($this->name, $descriptions) ? $descriptions[$this->name] : null;
        $this->description = $description ? $description["description"] : "";
        $this->required = !$parameter->isDefaultValueAvailable();

        $type = ToolDefinitionParameter::resolveType($parameter->getType());
        $descriptionType = ToolDefinitionParameter::resolveTypeFromString($description["type"]);

        $this->type = $type["name"];

        if (strlen($descriptionType["name"]) && (!strlen($this->type) || $this->type == "array")) {
            $this->type = $descriptionType["name"];
        }

        $this->nullable = $type["nullable"] ?? $descriptionType["nullable"];
        $this->values = $type["values"] ?? $descriptionType["values"];
    }

    private static function resolveEnumType(string $name): array {
        $type = "";
        $values = array();

        try {
            $enum = new \ReflectionEnum($name);
        }
        catch (\ReflectionException $e) {
            throw new \Exception("Type \"".$name."\" is not an Enum.");
        }

        if ($enum->isBacked()) {
            $backingType = static::resolveType($enum->getBackingType());
            $type = $backingType["name"];
            $values = array_map(fn (\ReflectionEnumBackedCase $case) => $case->getBackingValue(), $enum->getCases());
        }
        else {
            $type = "string";
            $values = array_map(fn (\ReflectionEnumUnitCase $case) => $case->getName(), $enum->getCases());
        }

        return array(
            "name" => $type,
            "values" => $values,
        );
    }

    private static function resolveTypeFromString(string $type): array {
        $builtInTypes = array("string", "int", "float", "double", "bool");
        $name = $type;
        $nullable = false;
        $values = null;

        if (strlen($type)) {
            if (str_starts_with($type, "?")) {
                $nullable = true;
                $name = substr($name, 1);
            }

            if (in_array($name, $builtInTypes)) {
                if ($name == "int" || $name == "double") {
                    $name = "number";
                }
            }
            else {
                $parts = array_filter(preg_split("/<|>/", $name), fn (string $s) => strlen($s) > 0);
                
                if (count($parts) > 2) {
                    throw new \Exception("Unsupported type \"".$type."\" for tools parameters: too deep.");
                }

                if ($parts[0] == "array") {
                    if (count($parts) > 1) {
                        $itemsType = static::resolveTypeFromString($parts[1]);
                        $name = $itemsType["name"]."[]";
                        $values = $itemsType["values"];
                    }
                }
                else {
                    try {
                        $enumType = static::resolveEnumType($name);
                        $name = $enumType["name"];
                        $values = $enumType["values"];
                    }
                    catch (\Exception $e) {
                        throw new \Exception("Unsupported type \"".$type."\" for tools parameters.");
                    }
                }
            }
        }

        return array(
            "name" => $name,
            "nullable" => $nullable,
            "values" => $values,
        );
    }

    private static function resolveType(?\ReflectionType $type): array {
        $sType = !is_null($type) ? ($type->allowsNull() ? "?" : "").$type->getName() : "";
        return static::resolveTypeFromString($sType);
    }
}

class ToolDefinition {
    private string $name;
    private string $description;
    private array $parameters;

    private function __construct(string $name) {
        $this->name = $name;
        $this->description = "";
        $this->parameters = array();
    }

    public function getName(): string {
        return $this->name;
    }

    public function getDescription(): string {
        return $this->description;
    }

    public function getParameters(): array {
        return $this->parameters;
    }

    private static function parseDescriptionsFromDocComment(string $doc): array {
        $descriptions = array(
            "description" => "",
            "parameters" => array()
        );

        $doc = preg_replace("/^\s*(?:\/\*)?\*\/?[ \t]*/m", "", $doc);

        if (preg_match("/^[^@]+/", $doc, $matches)) {
            $descriptions["description"] = trim($matches[0]);
        }

        if (preg_match_all("/^@param\s+(?:(?P<type>[^\\$\s]+)\s+)?\\$(?P<name>[^\s]+)(?P<description>.*)$/m", $doc, $matches, PREG_SET_ORDER)) {
            foreach ($matches as $match) {
                $descriptions["parameters"][$match["name"]] = array(
                    "description" => trim($match["description"]),
                    "type" => $match["type"],
                );
            }
        }

        return $descriptions;
    }

    public static function create(\ReflectionMethod $method): ToolDefinition {
        $tool = new ToolDefinition($method->getName());
        $docComment = $method->getDocComment();
        $parametersDescriptions = array();

        if ($docComment) {
            $descs = static::parseDescriptionsFromDocComment($docComment);
            $tool->description = $descs["description"];
            $parametersDescriptions = $descs["parameters"];
        }

        foreach ($method->getParameters() as $parameter) {
            $tool->parameters[] = new ToolDefinitionParameter($parameter, $parametersDescriptions);
        }

        return $tool;
    }
}

class Agent {
    protected string $apiPath;
    protected string $model;
    protected array $tools;

    protected function __construct(string $apiPath, string $model) {
        $this->apiPath = $apiPath;
        $this->model = $model;
        $this->tools = $this->getToolsDefinitions();

        var_dump($this->tools);
    }

    protected function ask(string $message): string {
        return $message;
    }

    private function getToolsDefinitions() {
        $tools = array();

        $reflection = new \ReflectionClass(static::class);
        foreach ($reflection->getMethods() as $method) {
            foreach ($method->getAttributes(Tool::class) as $attribute) {
                $tool = ToolDefinition::create($method);
                $tools[$tool->getName()] = $tool;
            }
        }

        return $tools;
    }
}
