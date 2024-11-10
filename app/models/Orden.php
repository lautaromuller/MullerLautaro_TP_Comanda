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


    public function crearOrden($productos)
    {
        $objAccesoDatos = AccesoDatos::obtenerInstancia();

        $codigoPedido = substr(str_shuffle("0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZ"), 0, 5);
        foreach ($productos as $producto) {
            $consultaStock = $objAccesoDatos->prepararConsulta("SELECT cantidad, sector FROM productos WHERE nombre = :nombre");
            $consultaStock->bindValue(':nombre', $producto['nombre'], PDO::PARAM_STR);
            $consultaStock->execute();

            $productoDB = $consultaStock->fetch(PDO::FETCH_ASSOC);

            if (!$productoDB || $productoDB['cantidad'] < $producto['cantidad']) {
                throw new Exception("Stock insuficiente para el producto: " . $producto['nombre']);
            }
        }

        $consultaOrden = $objAccesoDatos->prepararConsulta(
            "INSERT INTO ordenes (codigo_mesa, nombre_cliente, fecha, codigo_pedido, foto, estado) VALUES (:codigo_mesa, :nombre_cliente, :fecha, :codigo_pedido, :foto, :estado)"
        );
        $fecha = new DateTime(date("Y-m-d"));
        $consultaOrden->bindValue(':codigo_mesa', $this->codigo_mesa, PDO::PARAM_STR);
        $consultaOrden->bindValue(':fecha', $fecha->format('Y-m-d'));
        $consultaOrden->bindValue(':nombre_cliente', $this->nombre_cliente, PDO::PARAM_STR);
        $consultaOrden->bindValue(':codigo_pedido', $codigoPedido, PDO::PARAM_STR);
        $consultaOrden->bindValue(':foto', $this->foto, PDO::PARAM_STR);
        $consultaOrden->bindValue(':estado', "en preparación", PDO::PARAM_STR);
        $consultaOrden->execute();

        $consultaOrden = $objAccesoDatos->prepararConsulta(
            "UPDATE mesas SET estado = :estado WHERE codigo_mesa = :codigo_mesa"
        );
        $consultaOrden->bindValue(':codigo_mesa', $this->codigo_mesa, PDO::PARAM_STR);
        $consultaOrden->bindValue(':estado', "con cliente esperando pedido", PDO::PARAM_STR);
        $consultaOrden->execute();

        foreach ($productos as $producto) {
            $consultaStock = $objAccesoDatos->prepararConsulta("SELECT sector FROM productos WHERE nombre = :nombre");
            $consultaStock->bindValue(':nombre', $producto['nombre'], PDO::PARAM_STR);
            $consultaStock->execute();

            $productoDB = $consultaStock->fetch(PDO::FETCH_ASSOC);

            $consultaOrdenProducto = $objAccesoDatos->prepararConsulta(
                "INSERT INTO productos_ordenes (codigo_pedido, nombre_producto, cantidad, sector, estado) VALUES (:codigo_pedido, :nombre_producto, :cantidad, :sector, :estado)"
            );
            $consultaOrdenProducto->bindValue(':codigo_pedido', $codigoPedido, PDO::PARAM_STR);
            $consultaOrdenProducto->bindValue(':nombre_producto', $producto['nombre'], PDO::PARAM_STR);
            $consultaOrdenProducto->bindValue(':cantidad', $producto['cantidad'], PDO::PARAM_INT);
            $consultaOrdenProducto->bindValue(':sector', $productoDB['sector'], PDO::PARAM_STR);
            $consultaOrdenProducto->bindValue(':estado', "en preparación", PDO::PARAM_STR);
            $consultaOrdenProducto->execute();

            $consultaActStock = $objAccesoDatos->prepararConsulta("UPDATE productos SET cantidad = cantidad - :cantidad WHERE nombre = :nombre");
            $consultaActStock->bindValue(':cantidad', $producto['cantidad'], PDO::PARAM_INT);
            $consultaActStock->bindValue(':nombre', $producto['nombre'], PDO::PARAM_STR);
            $consultaActStock->execute();

            $consultaStock = $objAccesoDatos->prepararConsulta("SELECT cantidad FROM productos");
            $consultaStock->execute();
            $productoDB = $consultaStock->fetch(PDO::FETCH_ASSOC);

            if (!$productoDB || $productoDB['cantidad'] == 0) {
                $consultaStock = $objAccesoDatos->prepararConsulta("DELETE FROM productos WHERE nombre = :nombre");
                $consultaStock->bindValue(':nombre', $producto['nombre'], PDO::PARAM_STR);
                $consultaStock->execute();
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

    public static function modificarOrden($codigo_pedido, $codigo_mesa, $nombre_cliente, $productos)
    {
        $objAccesoDatos = AccesoDatos::obtenerInstancia();

        $consulta = $objAccesoDatos->prepararConsulta("UPDATE ordenes SET codigo_mesa = :codigo_mesa, nombre_cliente = :nombre_cliente WHERE codigo_pedido = :codigo_pedido");
        $consulta->bindValue(':codigo_mesa', $codigo_mesa, PDO::PARAM_STR);
        $consulta->bindValue(':nombre_cliente', $nombre_cliente, PDO::PARAM_STR);
        $consulta->bindValue(':codigo_pedido', $codigo_pedido, PDO::PARAM_STR);
        $consulta->execute();

        $consultaEliminar = $objAccesoDatos->prepararConsulta("DELETE FROM productos_ordenes WHERE codigo_pedido = :codigo_pedido ");
        $consultaEliminar->bindValue(':codigo_pedido', $codigo_pedido, PDO::PARAM_STR);
        $consultaEliminar->execute();

        foreach ($productos as $producto) {
            $consultaStock = $objAccesoDatos->prepararConsulta("SELECT sector FROM productos WHERE nombre = :nombre");
            $consultaStock->bindValue(':nombre', $producto['nombre'], PDO::PARAM_STR);
            $consultaStock->execute();
            $productoDB = $consultaStock->fetch(PDO::FETCH_ASSOC);

            $consultaProducto = $objAccesoDatos->prepararConsulta("INSERT INTO productos_ordenes (codigo_pedido , nombre_producto, cantidad, sector) VALUES (:codigo_pedido , :nombre_producto, :cantidad, :sector)");
            $consultaProducto->bindValue(':codigo_pedido', $codigo_pedido, PDO::PARAM_STR);
            $consultaProducto->bindValue(':nombre_producto', $producto['nombre'], PDO::PARAM_STR);
            $consultaProducto->bindValue(':cantidad', $producto['cantidad'], PDO::PARAM_INT);
            $consultaProducto->bindValue(':sector', $productoDB['sector'], PDO::PARAM_INT);
            $consultaProducto->execute();
        }

        return "Orden actualizada con éxito.";
    }

    public static function borrarOrden($codigo_pedido)
    {
        $objAccesoDatos = AccesoDatos::obtenerInstancia();
        $consulta = $objAccesoDatos->prepararConsulta("SELECT codigo_mesa FROM ordenes WHERE codigo_pedido = :codigo_pedido");
        $consulta->bindValue(':codigo_pedido', $codigo_pedido, PDO::PARAM_STR);
        $consulta->execute();

        $productoDB = $consulta->fetch(PDO::FETCH_ASSOC);

        $objAccesoDatos = AccesoDatos::obtenerInstancia();
        $consulta = $objAccesoDatos->prepararConsulta("UPDATE mesas SET estado = :estado WHERE codigo_mesa = :codigo_mesa");
        $consulta->bindValue(':estado', "disponible", PDO::PARAM_STR);
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
}
