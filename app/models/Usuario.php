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

        $consulta = $objAccesoDatos->prepararConsulta("INSERT INTO usuarios (id_usuario, nombre, sector, clave, estado) VALUES (:id_usuario, :nombre, :sector, :clave, 'activo')");
        $claveHash = password_hash($this->clave, PASSWORD_DEFAULT);
        $consulta->bindValue(':id_usuario', $id_usuario, PDO::PARAM_INT);
        $consulta->bindValue(':nombre', ucfirst($this->nombre), PDO::PARAM_STR);
        $consulta->bindValue(':sector', strtolower($this->sector), PDO::PARAM_STR);
        $consulta->bindValue(':clave', $claveHash);
        $consulta->execute();

        $consulta = $objAccesoDatos->prepararConsulta("INSERT INTO registro_usuario (dia, horario, nombre, sector) VALUES (:dia, :horario, :nombre, :sector)");
        $fecha = new DateTime();
        $consulta->bindValue(':dia', date_format($fecha, 'Y-m-d'));
        $consulta->bindValue(':horario', date_format($fecha, 'H:i:s'));
        $consulta->bindValue(':nombre', ucfirst($this->nombre), PDO::PARAM_STR);
        $consulta->bindValue(':sector', strtolower($this->sector), PDO::PARAM_STR);
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

    public static function borrarUsuario($id, $accion)
    {
        $objAccesoDato = AccesoDatos::obtenerInstancia();
        $consulta = $objAccesoDato->prepararConsulta("UPDATE usuarios SET fecha_baja = :fechaBaja, estado = :estado WHERE id = :id");
        $fecha = new DateTime();
        $consulta->bindValue(':id', $id, PDO::PARAM_INT);
        $consulta->bindValue(':fechaBaja', date_format($fecha, 'Y-m-d'));
        $consulta->bindValue(':estado', $accion, PDO::PARAM_STR);
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

    public static function subirEncuesta($mesa, $restaurante, $mozo, $cocinero, $mensaje, $c_mesa, $c_pedido)
    {
        $db = AccesoDatos::obtenerInstancia();
        $consulta = $db->prepararConsulta(
            "INSERT INTO encuesta (mesa, restaurante, mozo, cocinero, mensaje, fecha, codigo_mesa, codigo_pedido, puntaje) 
            VALUES (:mesa, :restaurante, :mozo, :cocinero, :mensaje, :fecha, :codigo_mesa, :codigo_pedido, :puntaje)"
        );
        $fecha = new DateTime();
        $consulta->bindValue(':mesa', $mesa, PDO::PARAM_INT);
        $consulta->bindValue(':restaurante', $restaurante, PDO::PARAM_INT);
        $consulta->bindValue(':mozo', $mozo, PDO::PARAM_INT);
        $consulta->bindValue(':cocinero', $cocinero, PDO::PARAM_INT);
        $consulta->bindValue(':mensaje', $mensaje, PDO::PARAM_STR);
        $consulta->bindValue(':fecha', date_format($fecha, 'Y-m-d'), PDO::PARAM_STR);
        $consulta->bindValue(':codigo_mesa', $c_mesa, PDO::PARAM_STR);
        $consulta->bindValue(':codigo_pedido', $c_pedido, PDO::PARAM_STR);
        $consulta->bindValue(':puntaje', round((($mesa + $restaurante + $mozo + $cocinero) / 4)), PDO::PARAM_INT);
        $consulta->execute();

        $consulta = $db->prepararConsulta("UPDATE registro_mesas SET puntaje = :puntaje WHERE codigo_mesa = :c_mesa AND codigo_pedido = :c_pedido");
        $consulta->bindValue(':puntaje', round((($mesa + $restaurante + $mozo + $cocinero) / 4)), PDO::PARAM_INT);
        $consulta->bindValue(':codigo_mesa', $c_mesa, PDO::PARAM_STR);
        $consulta->bindValue(':codigo_pedido', $c_pedido, PDO::PARAM_STR);
        $consulta->execute();
    }

    public static function existeEncuesta($c_pedido)
    {
        $db = AccesoDatos::obtenerInstancia();
        $consulta = $db->prepararConsulta("SELECT COUNT(*) AS contador FROM encuesta WHERE codigo_pedido = :codigo_pedido");
        $consulta->bindValue(':codigo_pedido', $c_pedido, PDO::PARAM_STR);
        $consulta->execute();
        $resultado = $consulta->fetch(PDO::FETCH_ASSOC);

        if ($resultado['contador'] > 0) {
            return true;
        }
        return false;
    }

    public static function mejoresComentarios()
    {
        $db = AccesoDatos::obtenerInstancia();
        $consulta = $db->prepararConsulta("SELECT * FROM encuesta ORDER BY puntaje DESC LIMIT 10");
        $consulta->execute();
        return $consulta->fetchAll(PDO::FETCH_ASSOC);
    }

    public static function ultimoUsuario()
    {
        $db = AccesoDatos::obtenerInstancia();
        $consulta = $db->prepararConsulta("SELECT * FROM regitro_usuario ORDER BY dia DESC, horario DESC LIMIT 1");
        $consulta->execute();
        return $consulta->fetchObject('Usuario');
    }
}
