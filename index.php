<?php
require 'vendor/autoload.php';
//get directory above website root
$env_dir = dirname($_SERVER['DOCUMENT_ROOT'])."/";

$dotenv = Dotenv\Dotenv::createImmutable($env_dir);
$dotenv->load();
use Src\Controller\DocController;


header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: OPTIONS,GET,POST,PUT,DELETE");
header("Access-Control-Max-Age: 3600");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

$uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$uri = explode('/', $uri);

$requestMethod = $_SERVER["REQUEST_METHOD"];
$parameters = array_slice($uri,4);
$method = $uri[4] ;

// pass the request method:
$controller = new DocController($requestMethod);

$controller->processRequest($parameters);

?>
