<?php
require_once '../vendor/autoload.php';

use Firebase\JWT\JWT;

class AutentificadorJWT
{
    public static function CrearToken($datos)
    {
        $fecha = time();
        $payload = array(
            'sub' => $datos['id_usuario'],
            'iat' => $fecha,
            'exp' => $fecha + (3600),
            'data' => $datos
        );
        
        return JWT::encode($payload, $_ENV['CLAVE']);
    }

    public static function VerificarToken($token)
    {
        if (empty($token)) {
            throw new Exception("El token está vacío.");
        }

        try {
            $decodificado = JWT::decode(
                $token,
                $_ENV['CLAVE'],
                [$_ENV['ALGORITMO']]
            );
        } catch (Exception $e) {
            throw $e;
        }

        return $decodificado;
    }
}
