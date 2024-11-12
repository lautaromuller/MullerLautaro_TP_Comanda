<?php

use Slim\Psr7\Response;

class MesaDisponible
{
    public function __construct() {}

    public function __invoke($request, $handler)
    {
        $params = json_decode($request->getBody(), true);
        $codigo = $params['codigo_mesa'];

        $mesas = Mesa::obtenerTodos();

        foreach ($mesas as $mesa) {
            if ($mesa->codigo_mesa == $codigo && $mesa->estado == 'disponible') {
                return $handler->handle($request);
            }
        }
        
        $response = new Response();
        $response->getBody()->write(json_encode([
            'error' => "La mesa no está disponible"
        ]));
        return $response
            ->withHeader('Content-Type', 'application/json');
    }
}
