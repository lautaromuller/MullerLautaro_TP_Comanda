<?php
require_once './models/Producto.php';
require_once './interfaces/IApiUsable.php';

class ProductoController extends Producto implements IApiUsable
{
    public function CargarUno($request, $response, $args)
    {
        $parametros = $request->getParsedBody();

        $nombre = $parametros['nombre'];
        $precio = $parametros['precio'];
        $sector = $parametros['sector'];
        $cantidad = $parametros['cantidad'];
        $tiempo = $parametros['tiempo'];

        $producto = new Producto();
        $producto->nombre = $nombre;
        $producto->precio = $precio;
        $producto->sector = $sector;
        $producto->cantidad = $cantidad;
        $producto->tiempo = $tiempo;
        $producto->crearProducto();

        $payload = json_encode(array("mensaje" => "Producto creado con éxito"));

        $response->getBody()->write($payload);
        return $response->withHeader('Content-Type', 'application/json');
    }

    public function TraerTodos($request, $response, $args)
    {
        $lista = Producto::obtenerTodos();
        $payload = json_encode(array("listaProductos" => $lista));

        $response->getBody()->write($payload);
        return $response->withHeader('Content-Type', 'application/json');
    }

    public function TraerUno($request, $response, $args)
    {
        $id = $args['id'];
        $producto = Producto::obtenerProducto($id);
        $payload = json_encode($producto);

        $response->getBody()->write($payload);
        return $response->withHeader('Content-Type', 'application/json');
    }

    public function ModificarUno($request, $response, $args)
    {
        $parametros = $request->getParsedBody();
        $id = $args['id'];
        $nombre = $parametros['nombre'];
        $precio = $parametros['precio'];
        $sector = $parametros['sector'];
        $cantidad = $parametros['cantidad'];
        $tiempo = $parametros['tiempo'];

        Producto::modificarProducto($id, $nombre, $precio, $sector, $cantidad, $tiempo);

        $payload = json_encode(array("mensaje" => "Producto modificado con éxito"));

        $response->getBody()->write($payload);
        return $response->withHeader('Content-Type', 'application/json');
    }

    public function BorrarUno($request, $response, $args)
    {
        $id = $args['id'];
        Producto::borrarProducto($id);

        $payload = json_encode(array("mensaje" => "Producto borrado con éxito"));

        $response->getBody()->write($payload);
        return $response->withHeader('Content-Type', 'application/json');
    }

    public function CargarArchivo($request, $response, $args)
    {
        if (isset($_FILES['archivo_csv'])) {
            $ruta = $_FILES['archivo_csv']['tmp_name'];

            $res = Producto::cargarCSV($ruta);

            $response->getBody()->write(json_encode($res));
            return $response->withHeader('Content-Type', 'application/json');
        }

        return "Falta archivo CSV.";
    }

    public function DescargarArchivo($request, $response, $args)
    {
        Producto::descargarCSV();

        $response->getBody()->write(json_encode(array("mensaje" => "archivo descargado con éxito")));
        return $response->withHeader('Content-Type', 'application/json');
    }



    //consultas
    public function MasVendido($request, $response, $args)
    {
        $producto = Producto::verMasVendido();

        $response->getBody()->write(json_encode(array("producto más vendido" => $producto)));
        return $response->withHeader('Content-Type', 'application/json');
    }

    public function MenosVendido($request, $response, $args)
    {
        $producto = Producto::verMenosVendido();

        $response->getBody()->write(json_encode(array("producto menos vendido" => $producto)));
        return $response->withHeader('Content-Type', 'application/json');
    }

    public function Cancelados($request, $response, $args)
    {
        $lista = Producto::productosCancelados();
        $payload = ["cancelados" => $lista];

        $response->getBody()->write(json_encode($payload));
        return $response->withHeader('Content-Type', 'application/json');
    }
}
