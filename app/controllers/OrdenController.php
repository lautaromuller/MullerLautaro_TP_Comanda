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
        
        if (strlen($codigo_pedido) == 5) {
            $parametros = $request->getParsedBody();
            $mesaId = $parametros['codigo_mesa'];
            $nombreCliente = $parametros['nombre_cliente'];
            $productos = $parametros['productos'];

            Orden::modificarOrden($codigo_pedido, $mesaId, $nombreCliente, $productos);

            $payload = json_encode(array("mensaje" => "Orden modificada con éxito"));

            $response->getBody()->write($payload);
            return $response->withHeader('Content-Type', 'application/json');
        }
        else{
            $payload = json_encode(array("error" => "El código de pedido no puede estar vacío"));
            $response->getBody()->write($payload);
            return $response->withHeader('Content-Type', 'application/json')->withStatus(400);
        }
    }

    public function BorrarUno($request, $response, $args)
    {
        $codigo_pedido = $args['codigo_pedido'];
        Orden::borrarOrden($codigo_pedido);
        $payload = json_encode(array("mensaje" => "órden borrada con éxito"));

        $response->getBody()->write($payload);
        return $response->withHeader('Content-Type', 'application/json');
    }
}