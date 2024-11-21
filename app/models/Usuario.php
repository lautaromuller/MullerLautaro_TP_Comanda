<?php

class Usuario
{
    public $id;
    public $id_usuario;
    public $nombre;
    public $sector;
    public $clave;
    public $fecha_baja;

    public function crearUsuario()
    {
        $objAccesoDatos = AccesoDatos::obtenerInstancia();
        $id_usuario = null;
        do {
            $id_usuario = rand(0, 1000);
            $consultaId = $objAccesoDatos->prepararConsulta("SELECT COUNT(*) AS contador FROM usuarios WHERE id_usuario = :id_usuario");
            $consultaId->bindValue(':id_usuario', $id_usuario, PDO::PARAM_INT);
            $consultaId->execute();
            $resultado = $consultaId->fetch(PDO::FETCH_ASSOC);
        } while ($resultado['contador'] > 0);

        $consulta = $objAccesoDatos->prepararConsulta("INSERT INTO usuarios (id_usuario, nombre, sector, clave) VALUES (:id_usuario, :nombre, :sector, :clave)");
        $claveHash = password_hash($this->clave, PASSWORD_DEFAULT);
        $consulta->bindValue(':id_usuario', $id_usuario, PDO::PARAM_INT);
        $consulta->bindValue(':nombre', ucfirst($this->nombre), PDO::PARAM_STR);
        $consulta->bindValue(':sector', strtolower($this->sector), PDO::PARAM_STR);
        $consulta->bindValue(':clave', $claveHash);
        $consulta->execute();

        return $objAccesoDatos->obtenerUltimoId();
    }

    public static function obtenerTodos()
    {
        $objAccesoDatos = AccesoDatos::obtenerInstancia();
        $consulta = $objAccesoDatos->prepararConsulta("SELECT id, nombre, sector, clave, fecha_baja FROM usuarios");
        $consulta->execute();

        return $consulta->fetchAll(PDO::FETCH_CLASS, 'Usuario');
    }

    public static function obtenerUsuario($id_usuario)
    {
        $objAccesoDatos = AccesoDatos::obtenerInstancia();
        $consulta = $objAccesoDatos->prepararConsulta("SELECT id, nombre, sector, clave, fecha_baja FROM usuarios WHERE id_usuario = :id_usuario");
        $consulta->bindValue(':id_usuario', $id_usuario, PDO::PARAM_STR);
        $consulta->execute();

        return $consulta->fetchObject('Usuario');
    }

    public static function modificarUsuario($id, $nombre, $sector, $clave)
    {
        $objAccesoDato = AccesoDatos::obtenerInstancia();
        $consulta = $objAccesoDato->prepararConsulta("UPDATE usuarios SET nombre = :nombre, sector = :sector, clave = :clave WHERE id = :id");
        $consulta->bindValue(':nombre', ucfirst($nombre), PDO::PARAM_STR);
        $consulta->bindValue(':sector', strtolower($sector), PDO::PARAM_STR);
        $claveHash = password_hash($clave, PASSWORD_DEFAULT);
        $consulta->bindValue(':clave', $claveHash);
        $consulta->bindValue(':id', $id, PDO::PARAM_INT);
        $consulta->execute();
    }

    public static function borrarUsuario($id)
    {
        $objAccesoDato = AccesoDatos::obtenerInstancia();
        $consulta = $objAccesoDato->prepararConsulta("UPDATE usuarios SET fecha_baja = :fechaBaja WHERE id = :id");
        $fecha = new DateTime();
        $consulta->bindValue(':id', $id, PDO::PARAM_INT);
        $consulta->bindValue(':fechaBaja', date_format($fecha, 'Y-m-d'));
        $consulta->execute();
    }

    public static function autenticar($nombre, $clave)
    {
        $db = AccesoDatos::obtenerInstancia();
        $consulta = $db->prepararConsulta("SELECT id, id_usuario, nombre, sector, clave, fecha_baja FROM usuarios WHERE nombre = :nombre");
        $consulta->bindValue(':nombre', $nombre, PDO::PARAM_STR);
        $consulta->execute();

        $usuario = $consulta->fetch(PDO::FETCH_OBJ);

        if ($usuario && password_verify($clave, $usuario->clave)) {
            return [
                "id_usuario" => $usuario->id,
                "nombre" => $usuario->nombre,
                "sector" => $usuario->sector,
                "fecha_baja" => $usuario->fecha_baja
            ];
        }

        return null;
    }

    public static function descargarCSV()
    {
        $objAccesoDatos = AccesoDatos::obtenerInstancia();
        $consulta = $objAccesoDatos->prepararConsulta("SELECT * FROM usuarios");
        $consulta->execute();
        $usuarios = $consulta->fetchAll(PDO::FETCH_ASSOC);

        $nombreArchivo = "usuarios_" . date("d-m-Y") . ".csv";
        header('Content-Type: text/csv');
        header("Content-Disposition: attachment; filename={$nombreArchivo}");

        $res = fopen('php://output', 'w');

        if (!empty($usuarios)) {
            fputcsv($res, array_keys($usuarios[0]));
        }

        foreach ($usuarios as $usuario) {
            fputcsv($res, $usuario);
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
            $consultaExistente = $objAccesoDatos->prepararConsulta("SELECT * FROM usuarios WHERE nombre = :nombre");
            $consultaExistente->bindValue(':nombre', $datos[0], PDO::PARAM_STR);
            $consultaExistente->execute();
            $resultado = $consultaExistente->fetchObject('Usuario');

            if ($resultado) {
                if (password_verify($datos[2], $resultado->clave)) {
                    continue;
                }
            }

            $usuario = new usuario();
            $usuario->nombre = $datos[0];
            $usuario->sector = $datos[1];
            $usuario->clave = password_hash($datos[2], PASSWORD_DEFAULT);
            $usuario->crearusuario();
        }

        fclose($archivo);
        exit;
    }

    public static function subirEncuesta($mesa, $restaurante, $mozo, $cocinero, $critica){
        $db = AccesoDatos::obtenerInstancia();
        $consulta = $db->prepararConsulta("INSERT INTO encuesta (mesa, restaurante, mozo, cocinero, critica, fecha) VALUES (:mesa, :restaurante, :mozo, :cocinero, :critica, :fecha)");
        $fecha = new DateTime();
        $consulta->bindValue(':mesa', $mesa, PDO::PARAM_INT);
        $consulta->bindValue(':restaurante', $restaurante, PDO::PARAM_INT);
        $consulta->bindValue(':mozo', $mozo, PDO::PARAM_INT);
        $consulta->bindValue(':cocinero', $cocinero, PDO::PARAM_INT);
        $consulta->bindValue(':critica', $critica, PDO::PARAM_STR);
        $consulta->bindValue(':fecha', date_format($fecha, 'Y-m-d'), PDO::PARAM_STR);
        $consulta->execute();
    }
}
