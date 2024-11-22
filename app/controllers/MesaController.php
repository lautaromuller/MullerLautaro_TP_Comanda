<?php
require_once './models/Mesa.php';
require_once './interfaces/IApiUsable.php';

class MesaController extends Mesa implements IApiUsable
{
    public function CargarUno($request, $response, $args)
    {
        $parametros = $request->getParsedBody();

        $codigo = $parametros['codigo_mesa'];

        $mesa = new Mesa();
        $mesa->codigo_mesa = $codigo;
        $mesa->crearMesa();

        $payload = json_encode(array("mensaje" => "Mesa creada con éxito"));

        $response->getBody()->write($payload);
        return $response->withHeader('Content-Type', 'application/json');
    }

    public function TraerTodos($request, $response, $args)
    {
        $lista = Mesa::obtenerTodos();
        $payload = json_encode(array("listaMesas" => $lista));

        $response->getBody()->write($payload);
        return $response->withHeader('Content-Type', 'application/json');
    }

    public function TraerUno($request, $response, $args)
    {
        $codigo = $args['codigo_mesa'];
        $mesa = Mesa::obtenerMesa($codigo);
        $payload = json_encode($mesa);

        $response->getBody()->write($payload);
        return $response->withHeader('Content-Type', 'application/json');
    }

    public function ModificarUno($request, $response, $args)
    {
        $parametros = $request->getParsedBody();
        $sector = $request->getAttribute('sector_usuario');

        $codigo = $args['codigo_mesa'];
        $estado = strtolower($parametros['estado']);

        if (($sector == 'mozo' && ($estado == 'con cliente comiendo' || $estado == 'con cliente pagando')) || ($sector == 'socio' && $estado == 'disponible')) {
            Mesa::modificarMesa($codigo, $estado);

            $response->getBody()->write(json_encode(array("mensaje" => "Mesa modificada con éxito")));
            return $response->withHeader('Content-Type', 'application/json');
        } else {
            $response->getBody()->write(json_encode(array("mensaje" => "No tiene permisos para esta operación")));
            return $response->withHeader('Content-Type', 'application/json');
        }
    }

    public function BorrarUno($request, $response, $args)
    {
        $codigo = $args['codigo_mesa'];
        $mesa = Mesa::obtenerMesa($codigo);

        if (!$mesa) {
            $payload = json_encode(array("error" => "La mesa no existe"));
        } elseif ($mesa->estado == "disponible") {
            Mesa::borrarMesa($codigo);
            $payload = json_encode(array("mensaje" => "Mesa borrada con éxito"));
        } else {
            $payload = json_encode(array("mensaje" => "Mesa en uso, no se puede borrar"));
        }

        $response->getBody()->write($payload);
        return $response->withHeader('Content-Type', 'application/json');
    }

    public function CargarArchivo($request, $response, $args)
    {
        if (isset($_FILES['archivo_csv'])) {
            $ruta = $_FILES['archivo_csv']['tmp_name'];

            $res = Mesa::cargarCSV($ruta);

            $response->getBody()->write(json_encode($res));
            return $response->withHeader('Content-Type', 'application/json');
        }

        return "Falta archivo CSV.";
    }

    public function DescargarArchivo($request, $response, $args)
    {
        Mesa::descargarCSV();

        $response->getBody()->write(json_encode(array("mensaje" => "archivo cargado con éxito")));
        return $response->withHeader('Content-Type', 'application/json');
    }

    public function MesaMasUsada($request, $response, $args)
    {
        $mesa = Mesa::masUsada();

        $response->getBody()->write(json_encode(array("mesa más usada" => $mesa)));
        return $response->withHeader('Content-Type', 'application/json');
    }
}
