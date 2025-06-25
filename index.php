<?php

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: http://localhost:3000');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');
header("Access-Control-Allow-Credentials: true");

session_start();

if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS'){
    http_response_code(200);
    exit();
}


require_once 'app\core\App.php';
require_once 'app\core\Controller.php';
require_once 'app\controllers\TimeslotController.php';
require_once 'app\controllers\AuthController.php';
require_once 'app\controllers\AdminController.php';


$app = new App();

$uri = rtrim(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH), '/');

if ($uri === '' || $uri === '/timetable_backend') $uri = '/';

$publicRoutes = [
    '/',
    '/index',
    '/signup',
    '/logout'
];

if (
    !isset($_SESSION['email']) &&
    !in_array($uri, $publicRoutes)
) {
    http_response_code(401);
    echo json_encode([
        'error' => 'Authentication required',
        'message' => 'Please login first'
    ]);
    exit();
}


$app->loadConfig('database');
$app->loadConfig('app');

$app->run();

?>
