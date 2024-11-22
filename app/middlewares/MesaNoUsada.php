<?php

use Slim\Psr7\Response;

class MesaNoUsada
{
    public function __invoke($request, $handler)
    {
        $codigo = $request->getAttribute('codigo_mesa');

        $mesa = Mesa::obtenerMesa($codigo);

        if (!$mesa) {
            $response = new Response();
            $response->getBody()->write(json_encode(['error' => "La mesa no existe"]));
            return $response->withHeader('Content-Type', 'application/json');
        }
        if ($mesa["estado"] == "disponible") {
            return $handler->handle($request);
        }

        $response = new Response();
        $response->getBody()->write(json_encode(['error' => "La mesa está en uso"]));
        return $response->withHeader('Content-Type', 'application/json');
    }
}
