<?php
use Slim\Psr7\Response;

class VerificarJWT
{
    public function __invoke($request, $handler)
    {
        $token = $_COOKIE['token'] ?? '';

        $response = new Response();

        if (!$token) {
            $response->getBody()->write(json_encode(['error' => 'Token requerido']));
            return $response->withHeader('Content-Type', 'application/json');
        }

        try {
            $decodificado = AutentificadorJWT::VerificarToken($token);

            $rol = $decodificado->data->tipo;

            $this->verificarPermisos($request, $rol);

            return $handler->handle($request);
        } catch (Exception $e) {
            $response->getBody()->write(json_encode(['error' => $e->getMessage()]));
            return $response->withHeader('Content-Type', 'application/json');
        }
    }

    private function verificarPermisos($request, $rol)
    {
        $path = $request->getUri()->getPath();
        $metodo = $request->getMethod();

        if ($rol == 'mozo') {
            if (strpos($path, '/mesas') != false && ($metodo == 'GET' || $metodo == 'PUT')) {
                return;
            }

            if (strpos($path, '/ordenes') != false) {
                return;
            }
        }

        if ($rol == 'cocinero' || $rol == 'cervecero' || $rol == 'bartender') {
            if (strpos($path, '/ordenes') != false && $metodo == 'PUT') {
                return;
            }
        }

        if ($rol == 'socio') {
            if (strpos($path, '/usuarios') != false || strpos($path, '/mesas') != false || strpos($path, '/productos') != false) {
                return;
            }

            if (strpos($path, '/ordenes') != false && $metodo == 'GET') {
                return;
            }
        }

        throw new Exception('Acceso denegado');
    }
}