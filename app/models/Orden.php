<?php

class Orden
{
    public $id;
    public $codigo_mesa;
    public $fecha;
    public $codigo_pedido;
    public $foto;
    public $nombre_cliente;
    public $estado;

    private static function consultarSector($nombre)
    {
        $objAccesoDatos = AccesoDatos::obtenerInstancia();
        $consulta = $objAccesoDatos->prepararConsulta("SELECT sector FROM productos WHERE nombre = :nombre");
        $consulta->bindValue(':nombre', $nombre, PDO::PARAM_STR);
        $consulta->execute();
        return $consulta->fetch(PDO::FETCH_ASSOC)['sector'];
    }

    public function crearOrden($productos)
    {
        $objAccesoDatos = AccesoDatos::obtenerInstancia();
        $codigoPedido = substr(str_shuffle("0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZ"), 0, 5);;
        $fecha = (new DateTime())->format('Y-m-d');

        foreach ($productos as $producto) {
            $objAccesoDatos = AccesoDatos::obtenerInstancia();
            $consulta = $objAccesoDatos->prepararConsulta("SELECT cantidad FROM productos WHERE nombre = :nombre");
            $consulta->bindValue(':nombre', $producto['nombre'], PDO::PARAM_STR);
            $consulta->execute();
            $productoDB = $consulta->fetch(PDO::FETCH_ASSOC);

            if (!$producto || $productoDB['cantidad'] <= $producto['cantidad']) {
                throw new Exception("Stock insuficiente para el producto: " . $producto['nombre']);
            }
        }

        $consultaOrden = $objAccesoDatos->prepararConsulta(
            "INSERT INTO ordenes (codigo_mesa, fecha, nombre_cliente, codigo_pedido, foto, estado) 
            VALUES (:codigo_mesa, :fecha, :nombre_cliente, :codigo_pedido, :foto, :estado)"
        );
        $consultaOrden->bindValue(':codigo_mesa', $this->codigo_mesa, PDO::PARAM_STR);
        $consultaOrden->bindValue(':fecha', $fecha);
        $consultaOrden->bindValue(':nombre_cliente', $this->nombre_cliente, PDO::PARAM_STR);
        $consultaOrden->bindValue(':codigo_pedido', $codigoPedido, PDO::PARAM_STR);
        $consultaOrden->bindValue(':foto', $this->foto, PDO::PARAM_STR);
        $consultaOrden->bindValue(':estado', "pendiente", PDO::PARAM_STR);
        $consultaOrden->execute();

        $objAccesoDatos = AccesoDatos::obtenerInstancia();
        $consulta = $objAccesoDatos->prepararConsulta("UPDATE mesas SET estado = :estado WHERE codigo_mesa = :codigo_mesa");
        $consulta->bindValue(':codigo_mesa', $this->codigo_mesa, PDO::PARAM_STR);
        $consulta->bindValue(':estado', "con cliente esperando pedido");
        $consulta->execute();

        foreach ($productos as $producto) {
            $sector = self::consultarSector($producto['nombre']);

            $consultaOrdenProducto = $objAccesoDatos->prepararConsulta(
                "INSERT INTO productos_ordenes (codigo_pedido, nombre_producto, cantidad, sector, estado) 
                VALUES (:codigo_pedido, :nombre_producto, :cantidad, :sector, :estado)"
            );
            $consultaOrdenProducto->bindValue(':codigo_pedido', $codigoPedido, PDO::PARAM_STR);
            $consultaOrdenProducto->bindValue(':nombre_producto', $producto['nombre'], PDO::PARAM_STR);
            $consultaOrdenProducto->bindValue(':cantidad', $producto['cantidad'], PDO::PARAM_INT);
            $consultaOrdenProducto->bindValue(':sector', $sector, PDO::PARAM_STR);
            $consultaOrdenProducto->bindValue(':estado', "pendiente", PDO::PARAM_STR);
            $consultaOrdenProducto->execute();

            $consultaActStock = $objAccesoDatos->prepararConsulta(
                "UPDATE productos SET cantidad = cantidad - :cantidad WHERE nombre = :nombre"
            );
            $consultaActStock->bindValue(':cantidad', $producto['cantidad'], PDO::PARAM_INT);
            $consultaActStock->bindValue(':nombre', $producto['nombre'], PDO::PARAM_STR);
            $consultaActStock->execute();

            $objAccesoDatos = AccesoDatos::obtenerInstancia();
            $consulta = $objAccesoDatos->prepararConsulta("SELECT cantidad FROM productos WHERE nombre = :nombre");
            $consulta->bindValue(':nombre', $producto['nombre'], PDO::PARAM_STR);
            $consulta->execute();
            $producto = $consulta->fetch(PDO::FETCH_ASSOC);

            if ($producto && $producto['cantidad'] == 0) {
                $consulta = $objAccesoDatos->prepararConsulta("DELETE FROM productos WHERE nombre = :nombre");
                $consulta->bindValue(':nombre', $producto['nombre'], PDO::PARAM_STR);
                $consulta->execute();
            }
        }

        return $codigoPedido;
    }

    public static function obtenerTodos()
    {
        $objAccesoDatos = AccesoDatos::obtenerInstancia();
        $consulta = $objAccesoDatos->prepararConsulta("SELECT id, codigo_mesa, fecha, nombre_cliente, codigo_pedido, foto, estado FROM ordenes");
        $consulta->execute();

        return $consulta->fetchAll(PDO::FETCH_CLASS, 'Orden');
    }

    public static function obtenerOrden($codigo_pedido)
    {
        $objAccesoDatos = AccesoDatos::obtenerInstancia();
        $consulta = $objAccesoDatos->prepararConsulta("SELECT id, codigo_mesa, fecha, nombre_cliente, codigo_pedido, foto, estado FROM ordenes WHERE codigo_pedido = :codigo_pedido");
        $consulta->bindValue(':codigo_pedido', $codigo_pedido, PDO::PARAM_STR);
        $consulta->execute();

        return $consulta->fetchObject('Orden');
    }

    public static function ModificarEstadoOrden($codigo_pedido, $sector, $estado)
    {
        $objAccesoDatos = AccesoDatos::obtenerInstancia();

        $consulta = $objAccesoDatos->prepararConsulta("UPDATE productos_ordenes SET estado = :estado WHERE codigo_pedido = :codigo_pedido AND sector = :sector");
        $consulta->bindValue(':estado', $estado, PDO::PARAM_STR);
        $consulta->bindValue(':codigo_pedido', $codigo_pedido, PDO::PARAM_STR);
        $consulta->bindValue(':sector', $sector, PDO::PARAM_STR);
        $consulta->execute();

        $consultaVerificar = $objAccesoDatos->prepararConsulta("SELECT estado FROM productos_ordenes WHERE codigo_pedido = :codigo_pedido");
        $consultaVerificar->bindValue(':codigo_pedido', $codigo_pedido, PDO::PARAM_STR);
        $consultaVerificar->execute();
        $productos = $consultaVerificar->fetchAll(PDO::FETCH_ASSOC);

        $todosListos = true;
        foreach ($productos as $producto) {
            if ($producto['estado'] != 'listo para servir') {
                $todosListos = false;
                break;
            }
        }

        if ($todosListos) {
            $consultaActualizarOrden = $objAccesoDatos->prepararConsulta("UPDATE ordenes SET estado = :estado WHERE codigo_pedido = :codigo_pedido");
            $consultaActualizarOrden->bindValue(':codigo_pedido', $codigo_pedido, PDO::PARAM_STR);
            $consultaActualizarOrden->bindValue(':estado', 'listo para servir', PDO::PARAM_STR);
            $consultaActualizarOrden->execute();
        }

        return "Orden actualizada.";
    }

    public static function ModificarDatosOrden($codigo_pedido, $nombre_cliente, $productos)
    {
        $objAccesoDatos = AccesoDatos::obtenerInstancia();

        $consulta = $objAccesoDatos->prepararConsulta("UPDATE ordenes SET nombre_cliente = :nombre_cliente WHERE codigo_pedido = :codigo_pedido");
        $consulta->bindValue(':nombre_cliente', $nombre_cliente, PDO::PARAM_STR);
        $consulta->bindValue(':codigo_pedido', $codigo_pedido, PDO::PARAM_STR);
        $consulta->execute();

        $consultaEliminar = $objAccesoDatos->prepararConsulta("DELETE FROM productos_ordenes WHERE codigo_pedido = :codigo_pedido");
        $consultaEliminar->bindValue(':codigo_pedido', $codigo_pedido, PDO::PARAM_STR);
        $consultaEliminar->execute();

        foreach ($productos as $producto) {
            $consultaSector = $objAccesoDatos->prepararConsulta("SELECT sector FROM productos WHERE nombre = :nombre");
            $consultaSector->bindValue(':nombre', $producto['nombre'], PDO::PARAM_STR);
            $consultaSector->execute();
            $productoDB = $consultaSector->fetch(PDO::FETCH_ASSOC);

            $consultaProducto = $objAccesoDatos->prepararConsulta("INSERT INTO productos_ordenes (codigo_pedido, nombre_producto, cantidad, sector, estado) VALUES (:codigo_pedido, :nombre_producto, :cantidad, :sector, 'pendiente')");
            $consultaProducto->bindValue(':codigo_pedido', $codigo_pedido, PDO::PARAM_STR);
            $consultaProducto->bindValue(':nombre_producto', $producto['nombre'], PDO::PARAM_STR);
            $consultaProducto->bindValue(':cantidad', $producto['cantidad'], PDO::PARAM_INT);
            $consultaProducto->bindValue(':sector', $productoDB['sector'], PDO::PARAM_STR);
            $consultaProducto->execute();
        }

        return "Datos de la orden actualizados con éxito.";
    }

    public static function borrarOrden($codigo_pedido)
    {
        $objAccesoDatos = AccesoDatos::obtenerInstancia();
        $consulta = $objAccesoDatos->prepararConsulta("SELECT codigo_mesa FROM ordenes WHERE codigo_pedido = :codigo_pedido");
        $consulta->bindValue(':codigo_pedido', $codigo_pedido, PDO::PARAM_STR);
        $consulta->execute();

        $productoDB = $consulta->fetch(PDO::FETCH_ASSOC);

        $objAccesoDatos = AccesoDatos::obtenerInstancia();
        $consulta = $objAccesoDatos->prepararConsulta("UPDATE mesas SET estado = 'disponible' WHERE codigo_mesa = :codigo_mesa");
        $consulta->bindValue(':codigo_mesa', $productoDB["codigo_mesa"], PDO::PARAM_STR);
        $consulta->execute();

        $objAccesoDato = AccesoDatos::obtenerInstancia();
        $consulta = $objAccesoDato->prepararConsulta("DELETE FROM ordenes WHERE codigo_pedido = :codigo_pedido");
        $consulta->bindValue(':codigo_pedido', $codigo_pedido, PDO::PARAM_STR);
        $consulta->execute();

        $objAccesoDato = AccesoDatos::obtenerInstancia();
        $consulta = $objAccesoDato->prepararConsulta("DELETE FROM productos_ordenes WHERE codigo_pedido = :codigo_pedido");
        $consulta->bindValue(':codigo_pedido', $codigo_pedido, PDO::PARAM_STR);
        $consulta->execute();
    }

    public static function descargarCSV()
    {
        $objAccesoDatos = AccesoDatos::obtenerInstancia();
        $consulta = $objAccesoDatos->prepararConsulta("SELECT * FROM ordenes");
        $consulta->execute();
        $ordenes = $consulta->fetchAll(PDO::FETCH_ASSOC);

        $nombreArchivo = "ordenes_" . date("d-m-Y") . ".csv";
        header('Content-Type: text/csv');
        header("Content-Disposition: attachment; filename={$nombreArchivo}");

        $res = fopen('php://output', 'w');

        if (!empty($ordenes)) {
            fputcsv($res, array_keys($ordenes[0]));
        }

        foreach ($ordenes as $orden) {
            fputcsv($res, $orden);
        }

        fclose($res);
        exit;
    }

    public static function cargarCSV($ruta)
    {
        $archivo = fopen($ruta, 'r');
        fgetcsv($archivo);
        $objAccesoDatos = AccesoDatos::obtenerInstancia();

        while ($datos = fgetcsv($archivo)) {
            $consultaExistente = $objAccesoDatos->prepararConsulta("SELECT * FROM ordenes WHERE codigo_mesa = :codigo_mesa");
            $consultaExistente->bindValue(':codigo_mesa', $datos[0], PDO::PARAM_STR);
            $consultaExistente->execute();
            $resultado = $consultaExistente->fetchObject('Orden');

            if ($resultado) {
                continue;
            }

            try {
                $orden = new Orden();
                $orden->codigo_mesa = $datos[0];
                $orden->nombre_cliente = $datos[1];
                $orden->foto = $datos[2];
                if(isset($datos[3])){
                    $orden->crearOrden($datos[3]);
                } else {
                    return array("mensaje" => "Las ordenes deben tener productos");
                }
            } catch (Exception $e) {
                return array("mensaje" => $e->getMessage());
            }
        }

        fclose($archivo);
        return array("mensaje" => "Ordenes cargada con éxito");
    }
}
