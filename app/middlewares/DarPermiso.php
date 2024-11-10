<?php
use Slim\Psr7\Response;

class ValidarCampos
{
    private $camposAValidar;

    public function __construct(array $camposAValidar)
    {
        $this->camposAValidar = $camposAValidar;
    }

    public function __invoke($request, $handler)
    {
        $params = $request->getParsedBody();

        foreach ($this->camposAValidar as $campo) {
            if (!isset($params[$campo]) || empty($params[$campo])) {
                $response = new Response();
                $response->getBody()->write(json_encode([
                    'error' => "Datos incorrectos, falta el campo: " . $campo
                ]));
                return $response
                    ->withHeader('Content-Type', 'application/json')
                    ->withStatus(400);
            }
        }
        return $handler->handle($request);
    }
}