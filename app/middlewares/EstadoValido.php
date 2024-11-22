<?php

use Slim\Psr7\Response;

class EstadoValido
{
    private $estado;

    public function __construct($estado)
    {
        $this->estado = $estado;
    }

    public function __invoke($request, $handler)
    {
        $params = $request->getParsedBody();

        if ($params[$this->estado] == 'cerrada' || $params[$this->estado] == 'con cliente esperando pedido' || $params[$this->estado] == 'con cliente comiendo' || $params[$this->estado] == 'con cliente pagando' || $params[$this->estado] == 'disponible') {
            return $handler->handle($request);
        }

        $response = new Response();
        $response->getBody()->write(json_encode([
            'error' => "El estado puesto no es válido"
        ]));
        return $response
            ->withHeader('Content-Type', 'application/json');
    }
}
