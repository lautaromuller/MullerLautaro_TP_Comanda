<?php
require_once __DIR__ . '/../../vendor/autoload.php';

class Orden
{
    public $id;
    public $codigo_mesa;
    public $nombre_cliente;
    public $codigo_pedido;
    public $foto;
    public $estado_mesa;
    public $estado_pedido;
    public $inicio_preparacion;
    public $tiempo;
    public $precio_total;
    public $facturacion;


    private static function consultarSector($nombre)
    {
        $objAccesoDatos = AccesoDatos::obtenerInstancia();
        $consulta = $objAccesoDatos->prepararConsulta("SELECT sector FROM productos WHERE nombre = :nombre");
        $consulta->bindValue(':nombre', $nombre, PDO::PARAM_STR);
        $consulta->execute();
        return $consulta->fetch(PDO::FETCH_ASSOC)['sector'];
    }

    private static function consultarTiempo($nombre)
    {
        $objAccesoDatos = AccesoDatos::obtenerInstancia();
        $consulta = $objAccesoDatos->prepararConsulta("SELECT tiempo FROM productos WHERE nombre = :nombre");
        $consulta->bindValue(':nombre', $nombre, PDO::PARAM_STR);
        $consulta->execute();
        return $consulta->fetch(PDO::FETCH_ASSOC)['tiempo'];
    }

    private static function consultarPrecio($nombre)
    {
        $objAccesoDatos = AccesoDatos::obtenerInstancia();
        $consulta = $objAccesoDatos->prepararConsulta("SELECT precio FROM productos WHERE nombre = :nombre");
        $consulta->bindValue(':nombre', $nombre, PDO::PARAM_STR);
        $consulta->execute();
        return $consulta->fetch(PDO::FETCH_ASSOC)['precio'];
    }

    public function crearOrden($productos)
    {
        $objAccesoDatos = AccesoDatos::obtenerInstancia();
        $codigoPedido = substr(str_shuffle("0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZ"), 0, 5);

        //Revisar stock
        $precio_total = 0;
        foreach ($productos as $producto) {
            if (!isset($producto['nombre'], $producto['cantidad'])) {
                throw new Exception("El producto no tiene un nombre o cantidad válida.");
            }
            $objAccesoDatos = AccesoDatos::obtenerInstancia();
            $consulta = $objAccesoDatos->prepararConsulta("SELECT cantidad FROM productos WHERE nombre = :nombre");
            $consulta->bindValue(':nombre', $producto['nombre'], PDO::PARAM_STR);
            $consulta->execute();
            $productoDB = $consulta->fetch(PDO::FETCH_ASSOC);

            if (!$producto || $productoDB['cantidad'] <= $producto['cantidad']) {
                throw new Exception("Stock insuficiente para el producto: " . $producto['nombre']);
            }

            $precio_total += (self::consultarPrecio(ucfirst($producto['nombre'])) * $producto['cantidad']);
        }

        //Agregamos orden
        $consultaOrden = $objAccesoDatos->prepararConsulta(
            "INSERT INTO ordenes (codigo_mesa, nombre_cliente, codigo_pedido, foto, estado_mesa, estado_pedido, inicio_preparacion, tiempo, precio_total) 
            VALUES (:codigo_mesa, :nombre_cliente, :codigo_pedido, :foto, :estado_mesa, :estado_pedido, :inicio_preparacion, 0, :precio_total)"
        );
        $consultaOrden->bindValue(':codigo_mesa', $this->codigo_mesa, PDO::PARAM_STR);
        $consultaOrden->bindValue(':nombre_cliente', ucfirst($this->nombre_cliente), PDO::PARAM_STR);
        $consultaOrden->bindValue(':codigo_pedido', $codigoPedido, PDO::PARAM_STR);
        $consultaOrden->bindValue(':foto', $this->foto, PDO::PARAM_STR);
        $consultaOrden->bindValue(':estado_mesa', "con cliente esperando pedido", PDO::PARAM_STR);
        $consultaOrden->bindValue(':estado_pedido', "pendiente", PDO::PARAM_STR);
        $consultaOrden->bindValue(':inicio_preparacion', 0, PDO::PARAM_STR);
        $consultaOrden->bindValue(':precio_total', $precio_total, PDO::PARAM_INT);
        $consultaOrden->execute();

        //Actualizamos mesas
        $objAccesoDatos = AccesoDatos::obtenerInstancia();
        $consulta = $objAccesoDatos->prepararConsulta("UPDATE mesas SET estado = :estado, usos = usos + 1 WHERE codigo_mesa = :codigo_mesa");
        $consulta->bindValue(':codigo_mesa', $this->codigo_mesa, PDO::PARAM_STR);
        $consulta->bindValue(':estado', "con cliente esperando pedido");
        $consulta->execute();

        //Agregamos productos de la orden
        foreach ($productos as $producto) {
            $sector = self::consultarSector(ucfirst($producto['nombre']));
            $tiempo = self::consultarTiempo(ucfirst($producto['nombre']));

            if ($sector == null || $tiempo == null) {
                throw new Exception("No se encontró el sector o tiempo para el producto: " . $producto['nombre']);
            }

            if (empty($codigoPedido) || empty($producto['nombre']) || empty($producto['cantidad'])) {
                throw new Exception("Datos incompletos para insertar en productos_ordenes.");
            }

            $consultaOrdenProducto = $objAccesoDatos->prepararConsulta(
                "INSERT INTO productos_ordenes (codigo_pedido, nombre_producto, cantidad, sector, estado, tiempo) 
                VALUES (:codigo_pedido, :nombre_producto, :cantidad, :sector, :estado, :tiempo)"
            );
            $consultaOrdenProducto->bindValue(':codigo_pedido', $codigoPedido, PDO::PARAM_STR);
            $consultaOrdenProducto->bindValue(':nombre_producto', ucfirst($producto['nombre']), PDO::PARAM_STR);
            $consultaOrdenProducto->bindValue(':cantidad', $producto['cantidad'], PDO::PARAM_INT);
            $consultaOrdenProducto->bindValue(':sector', $sector, PDO::PARAM_STR);
            $consultaOrdenProducto->bindValue(':estado', "pendiente", PDO::PARAM_STR);
            $consultaOrdenProducto->bindValue(':tiempo', $tiempo, PDO::PARAM_INT);
            $consultaOrdenProducto->execute();

            //Actualizamos productos
            $consultaActStock = $objAccesoDatos->prepararConsulta(
                "UPDATE productos SET cantidad = cantidad - :cantidad, vendidos = vendidos + 1 WHERE nombre = :nombre"
            );
            $consultaActStock->bindValue(':cantidad', $producto['cantidad'], PDO::PARAM_INT);
            $consultaActStock->bindValue(':nombre', $producto['nombre'], PDO::PARAM_STR);
            $consultaActStock->execute();

            //Borramos productos sin stock
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
        $consulta = $objAccesoDatos->prepararConsulta("SELECT id, codigo_mesa, nombre_cliente, codigo_pedido, foto, estado_mesa, estado_pedido, inicio_preparacion, tiempo, precio_total FROM ordenes");
        $consulta->execute();

        return $consulta->fetchAll(PDO::FETCH_CLASS, 'Orden');
    }

    public static function obtenerOrden($codigo_pedido)
    {
        $objAccesoDatos = AccesoDatos::obtenerInstancia();
        $consulta = $objAccesoDatos->prepararConsulta("SELECT * FROM ordenes WHERE codigo_pedido = :codigo_pedido");
        $consulta->bindValue(':codigo_pedido', $codigo_pedido, PDO::PARAM_STR);
        $consulta->execute();

        return $consulta->fetchObject('Orden');
    }

    public static function obtenerPendientes($codigo_pedido, $sector)
    {
        $objAccesoDatos = AccesoDatos::obtenerInstancia();
        $consulta = $objAccesoDatos->prepararConsulta("SELECT codigo_pedido, nombre_producto, cantidad, tiempo FROM productos_ordenes WHERE codigo_pedido = :codigo_pedido AND sector = :sector AND estado = 'pendiente'");
        $consulta->bindValue(':codigo_pedido', $codigo_pedido, PDO::PARAM_STR);
        $consulta->bindValue(':sector', $sector, PDO::PARAM_STR);
        $consulta->execute();

        return $consulta->fetchAll(PDO::FETCH_CLASS);
    }

    public static function VerTiempoOrden($codigo_pedido, $codigo_mesa)
    {
        $objAccesoDatos = AccesoDatos::obtenerInstancia();
        $consulta = $objAccesoDatos->prepararConsulta("SELECT inicio_preparacion, tiempo FROM ordenes WHERE codigo_pedido = :codigo_pedido AND codigo_mesa = :codigo_mesa");
        $consulta->bindValue(':codigo_pedido', $codigo_pedido, PDO::PARAM_STR);
        $consulta->bindValue(':codigo_mesa', $codigo_mesa, PDO::PARAM_STR);
        $consulta->execute();

        return $consulta->fetch(PDO::FETCH_OBJ);
    }

    public static function ModificarEstadoOrden($codigo_pedido, $sector, $estado_pedido)
    {
        $objAccesoDatos = AccesoDatos::obtenerInstancia();
        $consulta = $objAccesoDatos->prepararConsulta("UPDATE productos_ordenes SET estado = :estado WHERE codigo_pedido = :codigo_pedido AND sector = :sector");
        $consulta->bindValue(':estado', $estado_pedido, PDO::PARAM_STR);
        $consulta->bindValue(':codigo_pedido', $codigo_pedido, PDO::PARAM_STR);
        $consulta->bindValue(':sector', $sector, PDO::PARAM_STR);
        $consulta->execute();

        $orden = Orden::obtenerOrden($codigo_pedido);

        $mayor = 0;
        if ($orden->estado_pedido != $estado_pedido && $estado_pedido == "en preparación") {
            $consultaVerificar = $objAccesoDatos->prepararConsulta("SELECT tiempo FROM productos_ordenes WHERE codigo_pedido = :codigo_pedido");
            $consultaVerificar->bindValue(':codigo_pedido', $codigo_pedido, PDO::PARAM_STR);
            $consultaVerificar->execute();
            $productos = $consultaVerificar->fetchAll(PDO::FETCH_ASSOC);

            foreach ($productos as $producto) {
                $tiempo = $producto["tiempo"];
                if ($tiempo > $mayor) {
                    $mayor = $tiempo;
                }
            }
        }

        $usuarioActual = Usuario::ultimoUsuario();
        $consulta = $objAccesoDatos->prepararConsulta("SELECT SUM(operaciones) AS total_operaciones FROM registro_operaciones WHERE nombre = :nombre AND sector = :sector");
        $consulta->bindValue(':nombre', $usuarioActual->nombre, PDO::PARAM_STR);
        $consulta->bindValue(':sector', $usuarioActual->sector, PDO::PARAM_STR);
        $consulta->execute();
        $resultado = $consulta->fetch(PDO::FETCH_ASSOC);
        $totalOperaciones = $resultado['total_operaciones'] ?? 0;

        if ($totalOperaciones > 0) {
            $consulta = $objAccesoDatos->prepararConsulta("UPDATE registro_operaciones SET operaciones = :operaciones WHERE nombre = :nombre AND sector = :sector");
            $consulta->bindValue(':nombre', $usuarioActual->nombre, PDO::PARAM_STR);
            $consulta->bindValue(':sector', $sector, PDO::PARAM_STR);
            $consulta->bindValue(':operaciones', ($totalOperaciones + 1), PDO::PARAM_INT);
            $consulta->execute();
        } else {
            $consulta = $objAccesoDatos->prepararConsulta("INSERT INTO registro_operaciones (sector, nombre, operaciones) VALUES (:sector, :nombre, 1)");
            $consulta->bindValue(':nombre', $usuarioActual->nombre, PDO::PARAM_STR);
            $consulta->bindValue(':sector', $sector, PDO::PARAM_STR);
            $consulta->execute();
        }

        $consultaVerificar = $objAccesoDatos->prepararConsulta("SELECT estado FROM productos_ordenes WHERE codigo_pedido = :codigo_pedido");
        $consultaVerificar->bindValue(':codigo_pedido', $codigo_pedido, PDO::PARAM_STR);
        $consultaVerificar->execute();
        $productos = $consultaVerificar->fetchAll(PDO::FETCH_ASSOC);

        $todos_listos = true;
        foreach ($productos as $producto) {
            if ($producto['estado'] != "listo para servir") {
                $todos_listos = false;
                break;
            }
        }

        if ($estado_pedido == "en preparación" || ($estado_pedido == "listo para servir" && $todos_listos)) {
            $consultaActualizarOrden = $objAccesoDatos->prepararConsulta("UPDATE ordenes SET estado_pedido = :estado_pedido, inicio_preparacion = :inicio_preparacion, tiempo = :tiempo WHERE codigo_pedido = :codigo_pedido");
            $horaInicio = (new DateTime())->format('H:i:s');
            $consultaActualizarOrden->bindValue(':codigo_pedido', $codigo_pedido, PDO::PARAM_STR);
            $consultaActualizarOrden->bindValue(':estado_pedido', $estado_pedido, PDO::PARAM_STR);
            $consultaActualizarOrden->bindValue(':inicio_preparacion', $horaInicio, PDO::PARAM_STR);
            $consultaActualizarOrden->bindValue(':tiempo', $mayor, PDO::PARAM_INT);
            $consultaActualizarOrden->execute();
        }

        return "Orden actualizada.";
    }

    public static function ModificarDatosOrden($codigo_pedido, $nombre_cliente, $productos)
    {
        $objAccesoDatos = AccesoDatos::obtenerInstancia();

        $consulta = $objAccesoDatos->prepararConsulta("UPDATE ordenes SET nombre_cliente = :nombre_cliente WHERE codigo_pedido = :codigo_pedido");
        $consulta->bindValue(':nombre_cliente', ucfirst($nombre_cliente), PDO::PARAM_STR);
        $consulta->bindValue(':codigo_pedido', $codigo_pedido, PDO::PARAM_STR);
        $consulta->execute();

        $consultaEliminar = $objAccesoDatos->prepararConsulta("DELETE FROM productos_ordenes WHERE codigo_pedido = :codigo_pedido");
        $consultaEliminar->bindValue(':codigo_pedido', $codigo_pedido, PDO::PARAM_STR);
        $consultaEliminar->execute();

        foreach ($productos as $producto) {
            $consultaSector = $objAccesoDatos->prepararConsulta("SELECT sector FROM productos WHERE nombre = :nombre");
            $consultaSector->bindValue(':nombre', ucfirst($producto['nombre']), PDO::PARAM_STR);
            $consultaSector->execute();
            $productoDB = $consultaSector->fetch(PDO::FETCH_ASSOC);

            $consultaProducto = $objAccesoDatos->prepararConsulta("INSERT INTO productos_ordenes (codigo_pedido, nombre_producto, cantidad, sector, estado) VALUES (:codigo_pedido, :nombre_producto, :cantidad, :sector, 'pendiente')");
            $consultaProducto->bindValue(':codigo_pedido', $codigo_pedido, PDO::PARAM_STR);
            $consultaProducto->bindValue(':nombre_producto', ucfirst($producto['nombre']), PDO::PARAM_STR);
            $consultaProducto->bindValue(':cantidad', $producto['cantidad'], PDO::PARAM_INT);
            $consultaProducto->bindValue(':sector', strtolower($productoDB['sector']), PDO::PARAM_STR);
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

        $productoDB = $consulta->fetch(PDO::FETCH_OBJ);

        $objAccesoDatos = AccesoDatos::obtenerInstancia();
        $consulta = $objAccesoDatos->prepararConsulta("UPDATE mesas SET estado = 'disponible' WHERE codigo_mesa = :codigo_mesa");
        $consulta->bindValue(':codigo_mesa', $productoDB->codigo_mesa, PDO::PARAM_STR);
        $consulta->execute();

        $objAccesoDato = AccesoDatos::obtenerInstancia();
        $consulta = $objAccesoDato->prepararConsulta("DELETE FROM ordenes WHERE codigo_pedido = :codigo_pedido");
        $consulta->bindValue(':codigo_pedido', $codigo_pedido, PDO::PARAM_STR);
        $consulta->execute();

        $consulta = $objAccesoDatos->prepararConsulta("SELECT nombre_producto FROM productos_ordenes WHERE codigo_pedido = :codigo_pedido");
        $consulta->bindValue(':codigo_pedido', $codigo_pedido, PDO::PARAM_STR);
        $consulta->execute();
        $nombres = $consulta->fetchAll(PDO::FETCH_ASSOC);

        foreach ($nombres as $producto) {
            $consulta = $objAccesoDatos->prepararConsulta(
                "UPDATE productos SET vendidos = vendidos - 1, cancelados = cancelados + 1 WHERE nombre = :nombre"
            );
            $consulta->bindValue(':nombre', $producto['nombre_producto'], PDO::PARAM_STR);
            $consulta->execute();
        }

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
                if (isset($datos[3])) {
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
