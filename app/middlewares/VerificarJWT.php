<?php

use Slim\Psr7\Response;

class VerificarJWT
{
    public function __invoke($request, $handler) {
        $header = $request->getHeader('Authorization');
        $token = isset($header[0]) ? str_replace('Bearer ', '', $header[0]) : null;

        $response = new Response();

        if (!$token) {
            $response->getBody()->write(json_encode(['error' => 'Token requerido']));
            return $response->withHeader('Content-Type', 'application/json');
        }

        try {
            $decodificado = AutentificadorJWT::VerificarToken($token);

            $sector = $decodificado->data->sector;

            $request = $request->withAttribute('sector_usuario', $sector);

            $this->verificarPermisos($request, $sector);

            return $handler->handle($request);
        } catch (Exception $e) {
            $response->getBody()->write(json_encode(['error' => $e->getMessage()]));
            return $response->withHeader('Content-Type', 'application/json');
        }
    }

    private function verificarPermisos($request, $sector)
    {
        $path = $request->getUri()->getPath();
        $metodo = $request->getMethod();

        if ($sector == 'mozo') {
            if (strpos($path, '/mesas') != false && ($metodo == 'GET' || $metodo == 'PUT')) {
                return;
            }

            if (strpos($path, '/productos') != false && $metodo == 'GET') {
                return;
            }

            if (strpos($path, '/ordenes') != false) {
                return;
            }

            if (strpos($path, '/ordenes_csv') != false && $metodo == 'GET') {
                return;
            }

            if(strpos($path, '/ordenes_csv') != false || strpos($path, '/mesas_csv') != false || strpos($path, '/usuarios_csv') != false || strpos($path, '/productos_csv') != false){
                return;
            }
        }

        if ($sector == 'cocina' || $sector == 'cerveceria' || $sector == 'bar') {
            if (strpos($path, '/ordenes') != false && ($metodo == 'PUT' || $metodo == 'GET')) {
                return;
            }

            if (strpos($path, '/productos') != false && $metodo == 'GET') {
                return;
            }

            if (strpos($path, '/pendientes') != false) {
                return;
            }

            if(strpos($path, '/ordenes_csv') != false || strpos($path, '/mesas_csv') != false || strpos($path, '/usuarios_csv') != false || strpos($path, '/productos_csv') != false){
                return;
            }
        }

        if ($sector == 'socio') {
            if (strpos($path, '/usuarios') != false || strpos($path, '/mesas') != false || strpos($path, '/productos') != false) {
                return;
            }

            if (strpos($path, '/ordenes') != false && $metodo == 'GET') {
                return;
            }

            if(strpos($path, '/ordenes_csv') != false || strpos($path, '/mesas_csv') != false || strpos($path, '/usuarios_csv') != false || strpos($path, '/productos_csv') != false){
                return;
            }
        }

        throw new Exception('Acceso denegado');
    }
}
