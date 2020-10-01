<?php
require 'vendor/autoload.php';

use Src\Controller\DocController;

header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: OPTIONS,GET,POST,PUT,DELETE");
header("Access-Control-Max-Age: 3600");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

$uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$uri = explode('/', $uri);

// all of our endpoints start with /person
// everything else results in a 404 Not Found
//if (!isset($uri[4])) {
//    header("HTTP/1.1 404 Not Found");
//    exit();
//}

// authenticate the request with Okta:
//if (! authenticate()) {
//    header("HTTP/1.1 401 Unauthorized");
//    exit('Unauthorized');
//}
$requestMethod = $_SERVER["REQUEST_METHOD"];
$parameters = array_slice($uri,4);
$method = $uri[4] ;

// pass the request method:
$controller = new DocController($requestMethod);

$controller->processRequest($parameters);

?>
