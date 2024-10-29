<?php

class Orden
{
    public $id;
    public $mesa_id;
    public $fecha;
    public $codigo_pedido;
    public $foto;
    public $nombre_cliente;
    public $estado;


    public function crearOrden($productos)
    {
        $objAccesoDatos = AccesoDatos::obtenerInstancia();

        $codigoPedido = substr(str_shuffle("0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZ"), 0, 5);
        foreach ($productos as $producto) {
            $consultaStock = $objAccesoDatos->prepararConsulta("SELECT cantidad FROM productos WHERE nombre = :nombre");
            $consultaStock->bindValue(':nombre', $producto['nombre'], PDO::PARAM_STR);
            $consultaStock->execute();

            $productoDB = $consultaStock->fetch(PDO::FETCH_ASSOC);

            if (!$productoDB || $productoDB['cantidad'] < $producto['cantidad']) {
                throw new Exception("Stock insuficiente para el producto: " . $producto['nombre']);
            }
        }

        $consultaOrden = $objAccesoDatos->prepararConsulta(
            "INSERT INTO ordenes (mesa_id, nombre_cliente, fecha, codigo_pedido, foto, estado) VALUES (:mesa_id, :nombre_cliente, :fecha, :codigo_pedido, :foto, :estado)"
        );
        $fecha = new DateTime(date("Y-m-d"));
        $consultaOrden->bindValue(':mesa_id', $this->mesa_id, PDO::PARAM_STR);
        $consultaOrden->bindValue(':nombre_cliente', $this->nombre_cliente, PDO::PARAM_STR);
        $consultaOrden->bindValue(':foto', $this->foto, PDO::PARAM_STR);
        $consultaOrden->bindValue(':fecha', $fecha->format('Y-m-d'));
        $consultaOrden->bindValue(':codigo_pedido', $codigoPedido, PDO::PARAM_STR);
        $consultaOrden->bindValue(':estado', "en preparación", PDO::PARAM_STR);
        $consultaOrden->execute();

        foreach ($productos as $producto) {
            $consultaOrdenProducto = $objAccesoDatos->prepararConsulta(
                "INSERT INTO productos_ordenes (codigo_pedido, nombre_producto, cantidad) VALUES (:codigo_pedido, :nombre_producto, :cantidad)"
            );
            $consultaOrdenProducto->bindValue(':codigo_pedido', $codigoPedido, PDO::PARAM_INT);
            $consultaOrdenProducto->bindValue(':nombre_producto', $producto['nombre'], PDO::PARAM_STR);
            $consultaOrdenProducto->bindValue(':cantidad', $producto['cantidad'], PDO::PARAM_INT);
            $consultaOrdenProducto->execute();

            $consultaActualizarStock = $objAccesoDatos->prepararConsulta(
                "UPDATE productos SET cantidad = cantidad - :cantidad WHERE nombre = :nombre"
            );
            $consultaActualizarStock->bindValue(':cantidad', $producto['cantidad'], PDO::PARAM_INT);
            $consultaActualizarStock->bindValue(':nombre', $producto['nombre'], PDO::PARAM_STR);
            $consultaActualizarStock->execute();
        }

        return $codigoPedido;
    }

    public static function obtenerTodos()
    {
        $objAccesoDatos = AccesoDatos::obtenerInstancia();
        $consulta = $objAccesoDatos->prepararConsulta("SELECT id, mesa_id, nombre_cliente, fecha, codigo_pedido, foto, estado FROM ordenes");
        $consulta->execute();

        return $consulta->fetchAll(PDO::FETCH_CLASS, 'Orden');
    }

    public static function obtenerOrden($codigo_pedido)
    {
        $objAccesoDatos = AccesoDatos::obtenerInstancia();
        $consulta = $objAccesoDatos->prepararConsulta("SELECT id, mesa_id, nombre_cliente, fecha, codigo_pedido, foto, estado FROM ordenes WHERE codigo_pedido = :codigo_pedido");
        $consulta->bindValue(':codigo_pedido', $codigo_pedido, PDO::PARAM_STR);
        $consulta->execute();

        return $consulta->fetchObject('Orden');
    }

    public static function modificarOrden($codigo_pedido, $mesa_id, $nombre_cliente, $productos)
    {
        $objAccesoDatos = AccesoDatos::obtenerInstancia();

        $consulta = $objAccesoDatos->prepararConsulta("UPDATE ordenes SET mesa_id = :mesa_id, nombre_cliente = :nombre_cliente WHERE codigo_pedido = :codigo_pedido");
        $consulta->bindValue(':mesa_id', $mesa_id, PDO::PARAM_STR);
        $consulta->bindValue(':nombre_cliente', $nombre_cliente, PDO::PARAM_STR);
        $consulta->bindValue(':codigo_pedido', $codigo_pedido, PDO::PARAM_STR);
        $consulta->execute();

        $consultaEliminar = $objAccesoDatos->prepararConsulta("DELETE FROM productos_ordenes WHERE codigo_pedido = :codigo_pedido ");
        $consultaEliminar->bindValue(':codigo_pedido', $codigo_pedido, PDO::PARAM_STR);
        $consultaEliminar->execute();

        foreach ($productos as $producto) {
            $consultaProducto = $objAccesoDatos->prepararConsulta("INSERT INTO productos_ordenes (codigo_pedido , nombre_producto, cantidad) VALUES (:codigo_pedido , :nombre_producto, :cantidad)");
            $consultaProducto->bindValue(':codigo_pedido', $codigo_pedido, PDO::PARAM_STR);
            $consultaProducto->bindValue(':nombre_producto', $producto['nombre'], PDO::PARAM_STR);
            $consultaProducto->bindValue(':cantidad', $producto['cantidad'], PDO::PARAM_INT);
            $consultaProducto->execute();
        }

        return "Orden actualizada con éxito.";
    }

    public static function borrarOrden($codigo_pedido)
    {
        $objAccesoDato = AccesoDatos::obtenerInstancia();
        $consulta = $objAccesoDato->prepararConsulta("DELETE FROM ordenes WHERE codigo_pedido = :codigo_pedido");
        $consulta->bindValue(':codigo_pedido', $codigo_pedido, PDO::PARAM_STR);
        $consulta->execute();
    }
}
