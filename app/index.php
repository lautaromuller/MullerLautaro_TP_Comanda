<?php
// Error Handling
error_reporting(-1);
ini_set('display_errors', 1);

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Server\RequestHandlerInterface;
use Slim\Factory\AppFactory;
use Slim\Routing\RouteCollectorProxy;
use Slim\Routing\RouteContext;

require __DIR__ . '/../vendor/autoload.php';

require_once './db/AccesoDatos.php';
// require_once './middlewares/Logger.php';

require_once './controllers/UsuarioController.php';
require_once './controllers/MesaController.php';
require_once './controllers/ProductoController.php';
require_once './controllers/OrdenController.php';

// Load ENV
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
$dotenv->safeLoad();

// Instantiate App
$app = AppFactory::create();
$app->setBasePath('/TP-Comanda/app');
// Add error middleware
$app->addErrorMiddleware(true, true, true);

// Add parse body
$app->addBodyParsingMiddleware();

// Routes
$app->group('/usuarios', function (RouteCollectorProxy $group) {
  $group->post('[/]', \UsuarioController::class . ':CargarUno');
  $group->get('[/]', \UsuarioController::class . ':TraerTodos');
  $group->get('/{nombre}/{tipo}', \UsuarioController::class . ':TraerUno');
  $group->put('/{id}', \UsuarioController::class . ':ModificarUno');
  $group->delete('/{id}', \UsuarioController::class . ':BorrarUno');
});

$app->group('/mesas', function (RouteCollectorProxy $group) {
  $group->post('[/]', [MesaController::class, 'CargarUno']);
  $group->get('[/]', [MesaController::class, 'TraerTodos']);
  $group->get('/{codigo}', [MesaController::class, 'TraerUno']);
  $group->put('/{codigo}', [MesaController::class, 'ModificarUno']);
  $group->delete('/{codigo}', [MesaController::class, 'BorrarUno']);
});

$app->group('/productos', function (RouteCollectorProxy $group) {
  $group->post('[/]', [ProductoController::class, 'CargarUno']);
  $group->get('[/]', [ProductoController::class, 'TraerTodos']);
  $group->get('/{id}', [ProductoController::class, 'TraerUno']);
  $group->put('/{id}', [ProductoController::class, 'ModificarUno']);
  $group->delete('/{id}', [ProductoController::class, 'BorrarUno']);
});

$app->group('/ordenes', function (RouteCollectorProxy $group) {
  $group->post('[/]', [OrdenController::class, 'CargarUno']);
  $group->get('[/]', [OrdenController::class, 'TraerTodos']);
  $group->get('/{codigo_pedido}', [OrdenController::class, 'TraerUno']);
  $group->put('/{codigo_pedido}', [OrdenController::class, 'ModificarUno']);
  $group->delete('/{codigo_pedido}', [OrdenController::class, 'BorrarUno']);
});

$app->get('[/]', function (Request $request, Response $response) {    
  $payload = json_encode(array("mensaje" => "hola"));
  
  $response->getBody()->write($payload);
  return $response->withHeader('Content-Type', 'application/json');
});

$app->run();
