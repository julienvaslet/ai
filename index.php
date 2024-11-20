<?php
require_once("common.php");
define("ApiPath", RootPath."/api");

// Load all API classes
$handle = opendir(ApiPath);
while (($file = readdir($handle)) !== false) {
    if ($file[0] == ".") {
        continue;
    }

    require_once(ApiPath."/".$file);
}
closedir($handle);

use api\Endpoint;

$httpMethod = $_SERVER["REQUEST_METHOD"];
$requestUri = $_SERVER["REQUEST_URI"];

Endpoint::process($httpMethod, $requestUri);
