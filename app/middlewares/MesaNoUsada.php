<?php

use Slim\Psr7\Response;

class MesaNoUsada
{
    public function __invoke($request, $handler)
    {
        $codigo = $request->getAttribute('codigo_mesa');

        $mesa = Mesa::obtenerMesa($codigo);

        if (!$mesa || $mesa['codigo_mesa'] != "con cliente esperando pedido") {
            $response = new Response();
            $response->getBody()->write(json_encode([
                'error' => "La mesa está en uso"
            ]));
            return $response
                ->withHeader('Content-Type', 'application/json');
        }

        return $handler->handle($request);
    }
}
