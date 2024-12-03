<?php

class Producto
{
    public $id;
    public $nombre;
    public $precio;
    public $sector;
    public $cantidad;
    public $tiempo;

    public function crearProducto()
    {
        $objAccesoDatos = AccesoDatos::obtenerInstancia();
        $consulta = $objAccesoDatos->prepararConsulta("INSERT INTO productos (nombre, precio, sector, cantidad, tiempo) VALUES (:nombre, :precio, :sector, :cantidad, :tiempo)");
        $consulta->bindValue(':nombre', ucfirst($this->nombre), PDO::PARAM_STR);
        $consulta->bindValue(':precio', $this->precio, PDO::PARAM_STR);
        $consulta->bindValue(':sector', strtolower($this->sector), PDO::PARAM_STR);
        $consulta->bindValue(':cantidad', $this->cantidad, PDO::PARAM_INT);
        $consulta->bindValue(':tiempo', $this->tiempo, PDO::PARAM_INT);
        $consulta->execute();

        return $objAccesoDatos->obtenerUltimoId();
    }

    public static function obtenerTodos()
    {
        $objAccesoDatos = AccesoDatos::obtenerInstancia();
        $consulta = $objAccesoDatos->prepararConsulta("SELECT id, nombre, precio, sector, cantidad, tiempo FROM productos");
        $consulta->execute();

        return $consulta->fetchAll(PDO::FETCH_CLASS, 'Producto');
    }

    public static function obtenerProducto($id)
    {
        $objAccesoDatos = AccesoDatos::obtenerInstancia();
        $consulta = $objAccesoDatos->prepararConsulta("SELECT id, nombre, precio, sector, cantidad, tiempo FROM productos WHERE id = :id");
        $consulta->bindValue(':id', $id, PDO::PARAM_INT);
        $consulta->execute();

        return $consulta->fetchObject('Producto');
    }

    public static function modificarProducto($id, $nombre, $precio, $sector, $cantidad, $tiempo)
    {
        $objAccesoDato = AccesoDatos::obtenerInstancia();
        $consulta = $objAccesoDato->prepararConsulta("UPDATE productos SET nombre = :nombre, precio = :precio, sector = :sector, cantidad = :cantidad, tiempo = :tiempo WHERE id = :id");
        $consulta->bindValue(':nombre', ucfirst($nombre), PDO::PARAM_STR);
        $consulta->bindValue(':precio', $precio, PDO::PARAM_STR);
        $consulta->bindValue(':sector', strtolower($sector), PDO::PARAM_STR);
        $consulta->bindValue(':cantidad', $cantidad, PDO::PARAM_INT);
        $consulta->bindValue(':tiempo', $tiempo, PDO::PARAM_INT);
        $consulta->bindValue(':id', $id, PDO::PARAM_INT);
        $consulta->execute();
    }

    public static function borrarProducto($id)
    {
        $objAccesoDato = AccesoDatos::obtenerInstancia();
        $consulta = $objAccesoDato->prepararConsulta("DELETE FROM productos WHERE id = :id");
        $consulta->bindValue(':id', $id, PDO::PARAM_INT);
        $consulta->execute();
    }

    public static function descargarCSV()
    {
        $objAccesoDatos = AccesoDatos::obtenerInstancia();
        $consulta = $objAccesoDatos->prepararConsulta("SELECT * FROM productos");
        $consulta->execute();
        $productos = $consulta->fetchAll(PDO::FETCH_ASSOC);

        $nombreArchivo = "productos_" . date("d-m-Y") . ".csv";
        header('Content-Type: text/csv');
        header("Content-Disposition: attachment; filename={$nombreArchivo}");

        $res = fopen('php://output', 'w');

        if (!empty($productos)) {
            fputcsv($res, array_keys($productos[0]));
        }

        foreach ($productos as $producto) {
            fputcsv($res, $producto);
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
            $consultaExistente = $objAccesoDatos->prepararConsulta("SELECT * FROM productos WHERE nombre = :nombre");
            $consultaExistente->bindValue(':nombre', $datos[0], PDO::PARAM_STR);
            $consultaExistente->execute();
            $resultado = $consultaExistente->fetchObject('Producto');
            if($resultado->cantidad){
                $cantActual = $resultado->cantidad;
            }
            if ($resultado) {
                Producto::borrarProducto($resultado->id);
            }

            try {
                $producto = new Producto();
                $producto->nombre = $datos[0];
                $producto->precio = $datos[1];
                $producto->sector = $datos[2];
                $producto->cantidad = $datos[3] + $cantActual;
                $producto->tiempo = $datos[4];
                $producto->crearproducto();
            } catch (Exception $e) {
                return array("mensaje" => "Error al cargar los productos: {$e->getMessage()}");
            }
        }

        fclose($archivo);
        return array("mensaje" => "Productos cargados con éxito");
    }


    //consultas
    public static function verMasVendido()
    {
        $objAccesoDatos = AccesoDatos::obtenerInstancia();
        $consulta = $objAccesoDatos->prepararConsulta("SELECT nombre,sector,vendidos FROM productos ORDER BY vendidos DESC LIMIT 1");
        $consulta->execute();
        return $consulta->fetch(PDO::FETCH_OBJ);
    }

    public static function verMenosVendido()
    {
        $objAccesoDatos = AccesoDatos::obtenerInstancia();
        $consulta = $objAccesoDatos->prepararConsulta("SELECT nombre, sector, vendidos FROM productos ORDER BY vendidos ASC LIMIT 1");
        $consulta->execute();
        return $consulta->fetch(PDO::FETCH_OBJ);
    }

    public static function productosCancelados()
    {
        $objAccesoDatos = AccesoDatos::obtenerInstancia();
        $consulta = $objAccesoDatos->prepararConsulta("SELECT nombre, sector, cancelados FROM productos WHERE cancelados > 0");
        $consulta->execute();
        return $consulta->fetchAll(PDO::FETCH_ASSOC);
    }
}
