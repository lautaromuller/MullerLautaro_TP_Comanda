<?php

use Slim\Psr7\Response;

class MesaExistente
{
    public function __construct() {}

    public function __invoke($request, $handler)
    {
        $params = $request->getParsedBody();
        $codigo = $params['codigo_mesa'];

        $mesas = Mesa::obtenerTodos();

        foreach ($mesas as $mesa) {
            if ($mesa->codigo_mesa == $codigo) {
                $response = new Response();
                $response->getBody()->write(json_encode([
                    'error' => "Ya existe una mesa con ese código"
                ]));
                return $response
                    ->withHeader('Content-Type', 'application/json');
            }
        }

        return $handler->handle($request);
    }
}