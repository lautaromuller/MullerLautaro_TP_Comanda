<?php
require_once './models/Orden.php';
require_once './interfaces/IApiUsable.php';

class OrdenController extends Orden implements IApiUsable
{
    public function cargarUno($request, $response, $args)
    {
        $parametros = $request->getParsedBody();

        $nombreCliente = $parametros['nombre_cliente'];
        $mesaId = $parametros['codigo_mesa'];
        $productos = $parametros['productos'];
        $foto = isset($parametros['foto']) ? $parametros['foto'] : null;

        $orden = new Orden();
        $orden->codigo_mesa = $mesaId;
        $orden->nombre_cliente = $nombreCliente;
        $orden->foto = $foto;
        $resultado = $orden->crearOrden($productos);

        $payload = json_encode(array("mensaje" => "Orden creada con éxito", "codigo" => $resultado));
        $response->getBody()->write($payload);

        return $response->withHeader('Content-Type', 'application/json');
    }

    public function TraerTodos($request, $response, $args)
    {
        $lista = Orden::obtenerTodos();
        $payload = json_encode(array("listaOrdenes" => $lista));

        $response->getBody()->write($payload);
        return $response->withHeader('Content-Type', 'application/json');
    }

    public function TraerUno($request, $response, $args)
    {
        $codigo_pedido = $args['codigo_pedido'];
        $orden = Orden::obtenerOrden($codigo_pedido);
        $payload = json_encode($orden);

        $response->getBody()->write($payload);
        return $response->withHeader('Content-Type', 'application/json');
    }

    public function ModificarUno($request, $response, $args)
    {
        $codigo_pedido = $args['codigo_pedido'];
        $rol = $request->getAttribute('rol_usuario');
        $parametros = $request->getParsedBody();

        if (strlen($codigo_pedido) == 5) {
            if ($rol == 'mozo') {

                $nombreCliente = $parametros['nombre_cliente'];
                $productos = $parametros['productos'];
                Orden::modificarDatosOrden($codigo_pedido, $nombreCliente, $productos);
            } else if ($rol == 'cocinero' || $rol == 'cervecero' || $rol == 'bartender') {

                $estado = $parametros['estado'];
                $sector = $parametros['sector'];
                Orden::modificarEstadoOrden($codigo_pedido, $sector, $estado);
            } else {
                $response->getBody()->write(json_encode(array("mensaje" => "Acesso denegado")));
                return $response->withHeader('Content-Type', 'application/json');
            }
        } else {
            $response->getBody()->write(json_encode(array("error" => "El código de pedido no puede estar vacío")));
            return $response->withHeader('Content-Type', 'application/json');
        }

        $response->getBody()->write(json_encode(array("mensaje" => "Orden modificada con éxito")));
        return $response->withHeader('Content-Type', 'application/json');
    }

    public function BorrarUno($request, $response, $args)
    {
        $codigo_pedido = $args['codigo_pedido'];
        Orden::borrarOrden($codigo_pedido);
        $payload = json_encode(array("mensaje" => "órden borrada con éxito"));

        $response->getBody()->write($payload);
        return $response->withHeader('Content-Type', 'application/json');
    }

    public function CargarArchivo($request, $response, $args)
    {
        if (isset($_FILES['archivo_csv'])) {
            $ruta = $_FILES['archivo_csv']['tmp_name'];

            $res = Orden::cargarCSV($ruta);

            $response->getBody()->write(json_encode($res));
            return $response->withHeader('Content-Type', 'application/json');
        }

        return "Falta archivo CSV.";
    }

    public function DescargarArchivo($request, $response, $args)
    {
        Orden::descargarCSV();

        $response->getBody()->write(json_encode(array("mensaje" => "archivo cargado con éxito")));
        return $response->withHeader('Content-Type', 'application/json');
    }
}
