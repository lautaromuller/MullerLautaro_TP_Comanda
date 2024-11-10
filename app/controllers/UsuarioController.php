<?php
require_once './models/Usuario.php';
require_once './interfaces/IApiUsable.php';

class UsuarioController extends Usuario implements IApiUsable
{
  public function CargarUno($request, $response, $args)
  {
    $parametros = $request->getParsedBody();
 
    $nombre = $parametros['nombre'];
    $tipo = $parametros['tipo'];
    $clave = $parametros['clave'];

    $usr = new Usuario();
    $usr->nombre = $nombre;
    $usr->tipo = $tipo;
    $usr->clave = $clave;
    $usr->crearUsuario();

    $payload = json_encode(array("mensaje" => "Usuario creado con exito"));

    $response->getBody()->write($payload);
    return $response->withHeader('Content-Type', 'application/json');
  }

  public function TraerUno($request, $response, $args)
  {
    $id_usuario = $args['id_usuario'];
    $usuario = Usuario::obtenerUsuario($id_usuario);
    $payload = json_encode($usuario);

    $response->getBody()->write($payload);
    return $response->withHeader('Content-Type', 'application/json');
  }

  public function TraerTodos($request, $response, $args)
  {
    $lista = Usuario::obtenerTodos();
    $payload = json_encode(array("listaUsuario" => $lista));

    $response->getBody()->write($payload);
    return $response->withHeader('Content-Type', 'application/json');
  }

  public function ModificarUno($request, $response, $args)
  {
    $parametros = $request->getParsedBody();

    $usuarioId = $args['id'];
    $nombre = $parametros['nombre'];
    $tipo = $parametros['tipo'];
    $clave = $parametros['clave'];

    Usuario::modificarUsuario($usuarioId, $nombre, $tipo, $clave);

    $payload = json_encode(array("mensaje" => "Usuario modificado con éxito"));

    $response->getBody()->write($payload);
    return $response->withHeader('Content-Type', 'application/json');
  }

  public function BorrarUno($request, $response, $args)
  {
    $usuarioId = $args['id'];
    Usuario::borrarUsuario($usuarioId);

    $payload = json_encode(array("mensaje" => "Usuario borrado con exito"));

    $response->getBody()->write($payload);
    return $response->withHeader('Content-Type', 'application/json');
  }
}
