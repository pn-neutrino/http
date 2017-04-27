<?php
require __DIR__ . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . 'vendor' . DIRECTORY_SEPARATOR . 'autoload.php';

use \Neutrino\Http\Uri;
use \Neutrino\Http\Standard\Method;
use \Neutrino\Http\Standard\StatusCode;

$uri = new Uri($_SERVER['REQUEST_URI']);

$parts = explode('/', trim($uri->path, '/'));

$httpCode = $parts[0];
$httpMessage = !empty($parts[1]) ? $parts[1] : StatusCode::message($httpCode);
$method = $_SERVER['REQUEST_METHOD'];
header("HTTP/1.1 $httpCode $httpMessage");
header("Status-Code: $httpCode $httpMessage");
header("Request-Method: {$method}");

$headers= [];
foreach($_SERVER as $key => $value) {
    if (substr($key, 0, 5) <> 'HTTP_') {
        continue;
    }
    $header = str_replace(' ', '-', ucwords(str_replace('_', ' ', strtolower(substr($key, 5)))));
    $headers[$header] = $value;
}

switch ($method) {
    case Method::GET:
    case Method::DELETE:
    case Method::HEAD:
        $query = $_GET;
        break;
    case Method::POST:
        $query = $_POST;
        break;
    default:
        parse_str(urldecode(file_get_contents("php://input")), $query);
}

if (!empty($query)) {
    if (isset($query['stream'])) {
        $output = implode('', range('1', '9')) . PHP_EOL;
        $loop = 10000;

        header('Content-Length: ' . (strlen($output) * $loop));

        for ($i = 0; $i < $loop; $i++) {
            echo $output;
            ob_flush();
            flush();
        }
    } else {
        echo json_encode($query);
    }
}
