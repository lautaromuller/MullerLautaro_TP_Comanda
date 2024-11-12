<?php

class Usuario
{
    public $id;
    public $id_usuario;
    public $nombre;
    public $tipo;
    public $clave;
    public $fecha_baja;

    public function crearUsuario()
    {
        $objAccesoDatos = AccesoDatos::obtenerInstancia();
        $consulta = $objAccesoDatos->prepararConsulta("INSERT INTO usuarios (id_usuario, nombre, tipo, clave) VALUES (:id_usuario, :nombre, :tipo, :clave)");
        $id_usuario = rand(0, 1000);
        $claveHash = password_hash($this->clave, PASSWORD_DEFAULT);
        $consulta->bindValue(':id_usuario', $id_usuario, PDO::PARAM_INT);
        $consulta->bindValue(':nombre', $this->nombre, PDO::PARAM_STR);
        $consulta->bindValue(':tipo', $this->tipo, PDO::PARAM_STR);
        $consulta->bindValue(':clave', $claveHash);
        $consulta->execute();

        return $objAccesoDatos->obtenerUltimoId();
    }

    public static function obtenerTodos()
    {
        $objAccesoDatos = AccesoDatos::obtenerInstancia();
        $consulta = $objAccesoDatos->prepararConsulta("SELECT id, nombre, tipo, clave, fecha_baja FROM usuarios");
        $consulta->execute();

        return $consulta->fetchAll(PDO::FETCH_CLASS, 'Usuario');
    }

    public static function obtenerUsuario($id_usuario)
    {
        $objAccesoDatos = AccesoDatos::obtenerInstancia();
        $consulta = $objAccesoDatos->prepararConsulta("SELECT id, nombre, tipo, clave, fecha_baja FROM usuarios WHERE id_usuario = :id_usuario");
        $consulta->bindValue(':id_usuario', $id_usuario, PDO::PARAM_STR);
        $consulta->execute();

        return $consulta->fetchObject('Usuario');
    }

    public static function modificarUsuario($id, $nombre, $tipo, $clave)
    {
        $objAccesoDato = AccesoDatos::obtenerInstancia();
        $consulta = $objAccesoDato->prepararConsulta("UPDATE usuarios SET nombre = :nombre, tipo = :tipo, clave = :clave WHERE id = :id");
        $consulta->bindValue(':nombre', $nombre, PDO::PARAM_STR);
        $consulta->bindValue(':tipo', $tipo, PDO::PARAM_STR);
        $claveHash = password_hash($clave, PASSWORD_DEFAULT);
        $consulta->bindValue(':clave', $claveHash);
        $consulta->bindValue(':id', $id, PDO::PARAM_INT);
        $consulta->execute();
    }

    public static function borrarUsuario($id)
    {
        $objAccesoDato = AccesoDatos::obtenerInstancia();
        $consulta = $objAccesoDato->prepararConsulta("UPDATE usuarios SET fecha_baja = :fechaBaja WHERE id = :id");
        $fecha = new DateTime(date("d-m-Y"));
        $consulta->bindValue(':id', $id, PDO::PARAM_INT);
        $consulta->bindValue(':fechaBaja', date_format($fecha, 'Y-m-d'));
        $consulta->execute();
    }

    public static function autenticar($nombre, $clave)
    {
        $db = AccesoDatos::obtenerInstancia();
        $consulta = $db->prepararConsulta("SELECT id, id_usuario, nombre, tipo, clave, fecha_baja FROM usuarios WHERE nombre = :nombre");
        $consulta->bindValue(':nombre', $nombre, PDO::PARAM_STR);
        $consulta->execute();

        $usuario = $consulta->fetch(PDO::FETCH_OBJ);

        if ($usuario && password_verify($clave, $usuario->clave)) {
            return [
                "id_usuario" => $usuario->id,
                "nombre" => $usuario->nombre,
                "tipo" => $usuario->tipo
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

            try {
                $usuario = new usuario();
                $usuario->nombre = $datos[0];
                $usuario->tipo = $datos[1];
                $usuario->clave = password_hash($datos[2], PASSWORD_DEFAULT);
                $usuario->crearusuario();
            } catch (Exception $e) {
                return array("mensaje" => "Error al cargar los usuarios: {$e->getMessage()}");
            }
        }

        fclose($archivo);
        return array("mensaje" => "Usuarios cargados con éxito");
    }
}
