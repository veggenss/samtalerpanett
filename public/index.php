<?php
require __DIR__ . '/../bootstrap.php';

use FastRoute\Dispatcher;
use FastRoute\RouteCollector;

use Spn\Controllers\AuthController;
use Spn\Controllers\ChatController;
use Spn\Controllers\UserController;

$dispatcher = FastRoute\simpleDispatcher(function(RouteCollector $r){
    //Root
    $r->addRoute('GET', '/', [AuthController::class, 'showLogin']);

    //Page
    $r->addRoute('GET', '/login', [AuthController::class, 'showLogin']);
    $r->addRoute('GET', '/register', [AuthController::class, 'showRegister']);
    $r->addRoute('GET', '/verify-email', [AuthController::class, 'showEmailVerify']);
    $r->addRoute('GET', '/password-reset', [AuthController::class, 'showPasswordReset']);
    $r->addRoute('GET', '/chat', [ChatController::class, 'showChat']);
    $r->addRoute('GET', '/chat/profile', [UserController::class, 'showProfile']);
    $r->addRoute('GET', '/logout', [UserController::class, 'logout']);

    //API
    $r->addRoute('POST', '/api/login', [AuthController::class, 'login']);
    $r->addRoute('POST', '/api/register', [AuthController::class, 'register']);
    $r->addRoute('POST', '/api/verify-email', [AuthController::class, 'handleEmailToken']);

    $r->addRoute('POST', '/api/get-user-logs', [ChatController::class, 'getUserLogs']);
    $r->addRoute('POST', '/api/make-conv', [ChatController::class, 'makeConversation']);

    $r->addRoute('POST', '/api/update-user', [UserController::class, 'updateUser']);
    $r->addRoute('POST', '/api/delete-user', [UserController::class, 'deleteUser']);
});

$httpMethod = $_SERVER['REQUEST_METHOD'];
$uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);

$publicRoutes = [
    '/login',
    '/register',
    '/api/login',
    '/api/register',
    '/password-reset',
    '/verify-email',
    '/api/verify-email'
];

if(!in_array($uri, $publicRoutes)){
    if(!isset($_SESSION['user']['id'])){
        header('Location: /login');
        exit;
    }
}

$routeInfo = $dispatcher->dispatch($httpMethod, $uri);

switch($routeInfo[0]){
    case Dispatcher::NOT_FOUND:
        http_response_code(404);
        echo 404 . " Not Found";
        break;

    case Dispatcher::METHOD_NOT_ALLOWED:
        http_response_code(405);
        $allowedMethods = $routeInfo[1];
        echo 405 . " Method Not Allowed: " . $allowedMethods;
        break;

    case Dispatcher::FOUND:
        [$class, $method] = $routeInfo[1];
        $vars = $routeInfo[2];
        call_user_func_array([new $class, $method], $vars);
        break;
}