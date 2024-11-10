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
        $consulta = $objAccesoDatos->prepararConsulta("INSERT INTO mesas (codigo_mesa, estado, fecha_baja) VALUES (:codigo_mesa, :estado, null)");
        $consulta->bindValue(':codigo_mesa', $this->codigo_mesa, PDO::PARAM_STR);
        $consulta->bindValue(':estado', "disponible", PDO::PARAM_STR);
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

        return $consulta->fetchObject('Mesa')?: false;

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
}