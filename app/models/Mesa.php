<?php

class Mesa
{
    public $id;
    public $codigo_mesa;
    public $estado;

    public function crearMesa()
    {
        $objAccesoDatos = AccesoDatos::obtenerInstancia();
        $consulta = $objAccesoDatos->prepararConsulta("INSERT INTO mesas (codigo_mesa, estado) VALUES (:codigo_mesa, 'disponible')");
        $consulta->bindValue(':codigo_mesa', $this->codigo_mesa, PDO::PARAM_STR);
        $consulta->execute();

        return $objAccesoDatos->obtenerUltimoId();
    }

    public static function obtenerTodos()
    {
        $objAccesoDatos = AccesoDatos::obtenerInstancia();
        $consulta = $objAccesoDatos->prepararConsulta("SELECT id, codigo_mesa, estado FROM mesas");
        $consulta->execute();

        return $consulta->fetchAll(PDO::FETCH_CLASS, 'Mesa');
    }

    public static function obtenerMesa($codigo_mesa)
    {
        $objAccesoDatos = AccesoDatos::obtenerInstancia();
        $consulta = $objAccesoDatos->prepararConsulta("SELECT id, codigo_mesa, estado FROM mesas WHERE codigo_mesa = :codigo_mesa");
        $consulta->bindValue(':codigo_mesa', $codigo_mesa, PDO::PARAM_STR);
        $consulta->execute();
        return $consulta->fetchObject('Mesa'); 
    }

    public static function modificarMesa($codigo_mesa, $estado)
    {
        $objAccesoDatos = AccesoDatos::obtenerInstancia();
        $consulta = $objAccesoDatos->prepararConsulta("UPDATE mesas SET estado = :estado WHERE codigo_mesa = :codigo_mesa");
        $consulta->bindValue(':estado', $estado, PDO::PARAM_STR);
        $consulta->bindValue(':codigo_mesa', $codigo_mesa, PDO::PARAM_STR);
        $consulta->execute();

        $consulta = $objAccesoDatos->prepararConsulta("UPDATE ordenes SET estado_mesa = :estado WHERE codigo_mesa = :codigo_mesa");
        $consulta->bindValue(':estado', $estado, PDO::PARAM_STR);
        $consulta->bindValue(':codigo_mesa', $codigo_mesa, PDO::PARAM_STR);
        $consulta->execute();
    }

    public static function borrarMesa($codigo)
    {
        $objAccesoDatos = AccesoDatos::obtenerInstancia();
        $consulta = $objAccesoDatos->prepararConsulta("UPDATE mesas SET estado = :estado WHERE codigo_mesa = :codigo_mesa");
        $consulta->bindValue(':codigo_mesa', $codigo, PDO::PARAM_STR);
        $consulta->bindValue(':estado', "cerrada");
        $consulta->execute();
    }

    public static function descargarCSV()
    {
        $objAccesoDatos = AccesoDatos::obtenerInstancia();
        $consulta = $objAccesoDatos->prepararConsulta("SELECT * FROM mesas");
        $consulta->execute();
        $mesas = $consulta->fetchAll(PDO::FETCH_ASSOC);

        $nombreArchivo = "mesas_" . date("d-m-Y") . ".csv";
        header('Content-Type: text/csv');
        header("Content-Disposition: attachment; filename={$nombreArchivo}");

        $res = fopen('php://output', 'w');

        if (!empty($mesas)) {
            fputcsv($res, array_keys($mesas[0]));
        }

        foreach ($mesas as $mesa) {
            fputcsv($res, $mesa);
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
            $consultaExistente = $objAccesoDatos->prepararConsulta("SELECT * FROM mesas WHERE codigo_mesa = :codigo_mesa");
            $consultaExistente->bindValue(':codigo_mesa', $datos[0], PDO::PARAM_STR);
            $consultaExistente->execute();
            $resultado = $consultaExistente->fetchObject('Mesa');

            if ($resultado) {
                continue;
            }

            try {
                $mesa = new mesa();
                $mesa->codigo_mesa = $datos[0];
                $mesa->crearmesa();
            } catch (Exception $e) {
                return array("mensaje" => "Error al cargar las mesas: {$e->getMessage()}");
            }
        }

        fclose($archivo);
        return array("mensaje" => "Mesas cargadas con éxito");
    }

    public static function registrarMesa($codigo_mesa, $codigo_pedido, $importe){
        $db = AccesoDatos::obtenerInstancia();
        $consulta = $db->prepararConsulta("INSERT INTO registro_mesas (codigo_mesa, codigo_pedido, importe, fecha) VALUES (:codigo_mesa, :codigo_pedido, :importe, :fecha)");
        $fecha = new DateTime();
        $consulta->bindValue(':codigo_mesa', $codigo_mesa, PDO::PARAM_STR);
        $consulta->bindValue(':codigo_pedido', $codigo_pedido, PDO::PARAM_STR);
        $consulta->bindValue(':importe', $importe, PDO::PARAM_INT);
        $consulta->bindValue(':fecha', date_format($fecha, 'Y-m-d'));
        $consulta->execute();


        $consultaMesas = $db->prepararConsulta("UPDATE mesas SET facturacion = facturacion + :importe WHERE codigo_mesa = :codigo_mesa");
        $consultaMesas->bindValue(':importe', $importe, PDO::PARAM_STR);
        $consultaMesas->bindValue(':codigo_mesa', $codigo_mesa, PDO::PARAM_STR);
        $consultaMesas->execute();

    }

    public static function obtenerMesaRegistrada($codigo_pedido)
    {
        $objAccesoDatos = AccesoDatos::obtenerInstancia();
        $consulta = $objAccesoDatos->prepararConsulta("SELECT * FROM registro_mesas WHERE codigo_pedido = :codigo_pedido");
        $consulta->bindValue(':codigo_pedido', $codigo_pedido, PDO::PARAM_STR);
        $consulta->execute();
        return $consulta->fetch(PDO::FETCH_OBJ);
    }


    //consulta
    public static function verMasUsada()
    {
        $db = AccesoDatos::obtenerInstancia();
        $consulta = $db->prepararConsulta("SELECT codigo_mesa, usos FROM mesas ORDER BY usos DESC LIMIT 1;");
        $consulta->execute();
        return $consulta->fetch(PDO::FETCH_ASSOC);
    }    

    public static function verMenosUsada()
    {
        $db = AccesoDatos::obtenerInstancia();
        $consulta = $db->prepararConsulta("SELECT codigo_mesa, usos FROM mesas ORDER BY usos ASC LIMIT 1;");
        $consulta->execute();
        return $consulta->fetch(PDO::FETCH_ASSOC);
    }

    public static function verMayorFacturacion()
    {
        $db = AccesoDatos::obtenerInstancia();
        $consulta = $db->prepararConsulta("SELECT codigo_mesa, facturacion FROM mesas ORDER BY facturacion DESC LIMIT 1;");
        $consulta->execute();
        return $consulta->fetch(PDO::FETCH_ASSOC);
    }

    public static function verMenorFacturacion()
    {
        $db = AccesoDatos::obtenerInstancia();
        $consulta = $db->prepararConsulta("SELECT codigo_mesa, facturacion FROM mesas ORDER BY facturacion ASC LIMIT 1;");
        $consulta->execute();
        return $consulta->fetch(PDO::FETCH_ASSOC);
    }

    public static function verMayorImporte()
    {
        $db = AccesoDatos::obtenerInstancia();
        $consulta = $db->prepararConsulta("SELECT codigo_mesa, importe, fecha FROM registro_mesas ORDER BY importe DESC LIMIT 1;");
        $consulta->execute();
        return $consulta->fetch(PDO::FETCH_ASSOC);
    }

    public static function verMenorImporte()
    {
        $db = AccesoDatos::obtenerInstancia();
        $consulta = $db->prepararConsulta("SELECT codigo_mesa, importe, fecha FROM registro_mesas ORDER BY importe ASC LIMIT 1;");
        $consulta->execute();
        return $consulta->fetch(PDO::FETCH_ASSOC);
    }
}
