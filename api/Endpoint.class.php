<?php

namespace api;

class HttpException extends \Exception {
    private static $httpMessages = array(
        200 => "OK",
        201 => "Created",
        204 => "No Content",
        400 => "Bad Request",
        401 => "Unauthorized",
        403 => "Forbidden",
        404 => "Not Found",
        500 => "Internal Server Error",
    );

    public function __construct($code, \Throwable $previous = null) {
        parent::__construct(static::getHttpMessage($code), $code, $previous);
    }

    public static function getHttpMessage($code) {
        if (!array_key_exists($code, static::$httpMessages)) {
            return "Unknown HTTP code";
        }

        return static::$httpMessages[$code];
    }
}

#[\Attribute(\Attribute::TARGET_CLASS)]
class Path {
    public readonly string $path;

    public function __construct(string $path) {
        $this->path = $path;
    }
}

class Endpoint {
    private static $endpoints = array();

    public function __construct(array $parameters) {
        foreach ($parameters as $parameter => $value) {
            $this->{$parameter} = $value;
        }
    }

    public function delete() {
        throw new HttpException(400);
    }

    public function get() {
        throw new HttpException(400);
    }

    public function patch(array $body) {
        throw new HttpException(400);
    }

    public function post(array $body) {
        throw new HttpException(400);
    }

    public function put(array $body) {
        throw new HttpException(400);
    }

    public static function process($httpMethod, $uri) {
        $endpoint = null;
        $uriParameters = array();
        $uriQuestionMark = strpos($uri, "?");

        if ($uriQuestionMark) {
            parse_str(substr($uri,$uriQuestionMark + 1), $uriParameters);
            $uri = substr($uri, 0, $uriQuestionMark);
        }

        foreach (Endpoint::$endpoints as $path => $class) {
            if (preg_match($path, $uri, $parameters)) {
                $parameters = array_merge($uriParameters, array_filter($parameters, 'is_string', ARRAY_FILTER_USE_KEY));
                $endpoint = new $class($parameters);
                break;
            }
        }

        if ($endpoint == null) {
            http_response_code(404);
            echo HttpException::getHttpMessage(404);
        }

        $response = null;
        $httpMethod = strtolower($httpMethod);

        try {
            switch ($httpMethod) {
                case "get": 
                case "delete": {
                    $response = $endpoint->{$httpMethod}();
                    break;
                }

                case "patch": 
                case "post": 
                case "put": {
                    $body = array();
                    parse_str(file_get_contents("php://input"), $body);

                    if (!is_null($_FILES) && count($_FILES)) {
                        foreach($_FILES as $key => $data) {
                            if (is_array($data["name"])) {
                                $files = array_fill(0, count($data["name"]) + 1, array());

                                foreach ($data as $attr => $values) {
                                    var_dump($values);
                                    foreach ($values as $index => $value) {
                                        $files[$index][$attr] = $value;
                                    }
                                }

                                $data = $files;
                            }

                            $body[$key] = $data;
                        }
                    }

                    $response = $endpoint->{$httpMethod}($body);
                    break;
                }

                default: {
                    throw new HttpException(400);
                }
            }
        }
        catch (HttpException $e) {
            http_response_code($e->getCode());
            echo $e->getMessage();
        }

        echo $response;
    }

    public static function register() {
        $class = static::class;
        $path = null;
        $reflection = new \ReflectionClass($class);
        foreach ($reflection->getAttributes() as $attribute) {
            if ($attribute->getName() == Path::class) {
                $attribute = $attribute->newInstance();
                $path = $attribute->path;
                break;
            }
        }

        if (!$path) {
            throw new \Exception("Endpoint \"${class}\" misconfigured: missing Path.");
        }

        Endpoint::$endpoints["`^${path}\$`"] = $class;
    }
}