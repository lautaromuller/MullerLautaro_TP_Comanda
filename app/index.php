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
require_once './middlewares/MesaExistente.php';
require_once './middlewares/MesaDisponible.php';
require_once './middlewares/ValidarCampos.php';
require_once './middlewares/MesaNoUsada.php';
require_once './middlewares/EstadoValido.php';
require_once './middlewares/VerificarJWT.php';

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
  $group->post('[/]', \UsuarioController::class . ':CargarUno')
    ->add(new ValidarCampos(array("nombre", "sector", "clave")));

  $group->get('[/]', \UsuarioController::class . ':TraerTodos');
  $group->get('/{id_usuario}', \UsuarioController::class . ':TraerUno');

  $group->put('/{id}', \UsuarioController::class . ':ModificarUno')
    ->add(new ValidarCampos(array("nombre", "sector", "clave")));

  $group->delete('/{id}/{accion}', \UsuarioController::class . ':BorrarUno');
})->add(new VerificarJWT());

$app->group('/mesas', function (RouteCollectorProxy $group) {
  $group->post('[/]', [MesaController::class, 'CargarUno'])
    ->add(new MesaExistente())
    ->add(new ValidarCampos(array("codigo_mesa")));

  $group->get('[/]', [MesaController::class, 'TraerTodos']);
  $group->get('/{codigo_mesa}', [MesaController::class, 'TraerUno']);

  $group->put('/{codigo_mesa}', [MesaController::class, 'ModificarUno'])
    ->add(new EstadoValido("estado"));

  $group->delete('/{codigo_mesa}', [MesaController::class, 'BorrarUno']);
})->add(new VerificarJWT());

$app->group('/productos', function (RouteCollectorProxy $group) {
  $group->post('[/]', [ProductoController::class, 'CargarUno'])
    ->add(new ValidarCampos(array("nombre", "precio", "sector", "cantidad")));

  $group->get('[/]', [ProductoController::class, 'TraerTodos']);
  $group->get('/{id}', [ProductoController::class, 'TraerUno']);
  $group->put('/{id}', [ProductoController::class, 'ModificarUno'])
    ->add(new ValidarCampos(array("nombre", "precio", "sector", "cantidad")));

  $group->delete('/{id}', [ProductoController::class, 'BorrarUno']);
})->add(new VerificarJWT());

$app->group('/ordenes', function (RouteCollectorProxy $group) {
  $group->post('[/]', [OrdenController::class, 'CargarUno'])
    ->add(new MesaDisponible())
    ->add(new ValidarCampos(array("codigo_mesa", "nombre_cliente", "productos")));

  $group->get('[/]', [OrdenController::class, 'TraerTodos']);
  $group->get('/{codigo_pedido}', [OrdenController::class, 'TraerUno']);

  $group->put('/{codigo_pedido}', [OrdenController::class, 'ModificarUno']);

  $group->delete('/{codigo_pedido}', [OrdenController::class, 'BorrarUno']);
})->add(new VerificarJWT());

$app->group('/clientes', function (RouteCollectorProxy $group) {
  $group->get('/{codigo_pedido}/{codigo_mesa}', [OrdenController::class, 'VerTiempoPedido']);
  $group->post('[/]', [UsuarioController::class, 'CargarEncuesta']);
});


$app->post('/registro', \UsuarioController::class . ':RegistrarUsuario');
$app->post('/login', \UsuarioController::class . ':Login');


$app->group('/ordenes_csv', function (RouteCollectorProxy $group) {
  $group->post('[/]', [OrdenController::class, 'CargarArchivo']);
  $group->get('[/]', [OrdenController::class, 'DescargarArchivo']);
})->add(new VerificarJWT());

$app->group('/mesas_csv', function (RouteCollectorProxy $group) {
  $group->post('[/]', [MesaController::class, 'CargarArchivo']);
  $group->get('[/]', [MesaController::class, 'DescargarArchivo']);
})->add(new VerificarJWT());

$app->group('/usuarios_csv', function (RouteCollectorProxy $group) {
  $group->post('[/]', [UsuarioController::class, 'CargarArchivo']);
  $group->get('[/]', [UsuarioController::class, 'DescargarArchivo']);
})->add(new VerificarJWT());

$app->group('/productos_csv', function (RouteCollectorProxy $group) {
  $group->post('[/]', [ProductoController::class, 'CargarArchivo']);
  $group->get('[/]', [ProductoController::class, 'DescargarArchivo']);
})->add(new VerificarJWT());



$app->get('/pendientes', [OrdenController::class, 'TraerPendientes'])->add(new VerificarJWT());
$app->get('/listos', [OrdenController::class, 'TraerListos'])->add(new VerificarJWT());
$app->get('/cobrar_comanda/{codigo_pedido}/{codigo_mesa}', [OrdenController::class, 'CobrarComanda'])->add(new VerificarJWT());
$app->get('/mejores_comentarios', [UsuarioController::class, 'MejoresComentarios'])->add(new VerificarJWT());




$app->get('/peores_comentarios', [UsuarioController::class, 'PeoresComentarios']);

$app->group('/consultar', function (RouteCollectorProxy $group) {
  $group->get('/empleados/operaciones_sector', [UsuarioController::class, 'OperacionesPorSector']);
  $group->get('/empleados/operaciones_usuarios', [UsuarioController::class, 'OperacionesPorUsuarios']);


  $group->get('/pedidos/mas_vendido', [ProductoController::class, 'MasVendido']);
  $group->get('/pedidos/menos_vendido', [ProductoController::class, 'MenosVendido']);
  $group->get('/pedidos/cancelados', [ProductoController::class, 'Cancelados']);


  $group->get('/mesas/mas_usada', [MesaController::class, 'MasUsada']);
  $group->get('/mesas/menos_usada', [MesaController::class, 'MenosUsada']);
  $group->get('/mesas/mas_facturacion', [MesaController::class, 'MasFacturacion']);
  $group->get('/mesas/menos_facturacion', [MesaController::class, 'MenosFacturacion']);
  $group->get('/mesas/mayor_importe', [MesaController::class, 'MayorImporte']);
  $group->get('/mesas/menor_importe', [MesaController::class, 'MenorImporte']);
});

$app->post('/usuarios/alta_usuario', [UsuarioController::class, 'DarDeAlta']);



$app->get('[/]', [OrdenController::class, 'DescargarArchivoPDF']);

$app->run();
