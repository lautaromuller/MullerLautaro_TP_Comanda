<?php

class Mesa
{
    public $id;
    public $codigo_mesa;
    public $estado;
    public $fecha_baja;

    public function crearMesa()
    {
        $objAccesoDatos = AccesoDatos::obtenerInstancia();
        $consulta = $objAccesoDatos->prepararConsulta("INSERT INTO mesas (codigo_mesa, estado, fecha_baja) VALUES (:codigo_mesa, 'disponible', null)");
        $consulta->bindValue(':codigo_mesa', $this->codigo_mesa, PDO::PARAM_STR);
        $consulta->execute();

        return $objAccesoDatos->obtenerUltimoId();
    }

    public static function obtenerTodos()
    {
        $objAccesoDatos = AccesoDatos::obtenerInstancia();
        $consulta = $objAccesoDatos->prepararConsulta("SELECT id, codigo_mesa, estado, fecha_baja FROM mesas");
        $consulta->execute();

        return $consulta->fetchAll(PDO::FETCH_CLASS, 'Mesa');
    }

    public static function obtenerMesa($codigo)
    {
        $objAccesoDatos = AccesoDatos::obtenerInstancia();
        $consulta = $objAccesoDatos->prepararConsulta("SELECT id, codigo_mesa, estado, fecha_baja FROM mesas WHERE codigo_mesa = :codigo_mesa");
        $consulta->bindValue(':codigo_mesa', $codigo, PDO::PARAM_STR);
        $consulta->execute();

        return $consulta->fetchObject('Mesa') ?: false;
    }

    public static function modificarMesa($codigo, $estado)
    {
        $objAccesoDatos = AccesoDatos::obtenerInstancia();
        $consulta = $objAccesoDatos->prepararConsulta("UPDATE mesas SET estado = :estado WHERE codigo_mesa = :codigo_mesa");
        $consulta->bindValue(':estado', $estado, PDO::PARAM_STR);
        $consulta->bindValue(':codigo_mesa', $codigo, PDO::PARAM_STR);
        $consulta->execute();
    }

    public static function borrarMesa($codigo)
    {
        $objAccesoDatos = AccesoDatos::obtenerInstancia();
        $consulta = $objAccesoDatos->prepararConsulta("UPDATE mesas SET fecha_baja = :fechaBaja WHERE codigo_mesa = :codigo_mesa");
        $fecha = new DateTime(date("Y-m-d"));
        $consulta->bindValue(':codigo_mesa', $codigo, PDO::PARAM_STR);
        $consulta->bindValue(':fechaBaja', $fecha->format('Y-m-d'));
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
}
