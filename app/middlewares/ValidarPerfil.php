<?php
use Slim\Psr7\Response;

class ValidarPerfil{
    private $perfil = "";

    public function __construct($perfil)
    {
        $this->perfil = $perfil;
    }

    public function __invoke($request, $handler)
    {
        $response = new Response();
        $params = $request->getQueryParams();

        if(isset($params["credenciales"])){
            $credenciales = $params["credenciales"];

            if($credenciales == $this->perfil){
                $response = $handler->handle($request);

            } else{
                $response->getBody()->write(json_encode(array("error" => "No sos " . $this->perfil)));
            } 
        } else {
            $response->getBody()->write(json_encode(array("error" => "No hay credenciales")));
        }

        return $response;
    }
}