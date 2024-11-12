<?php
require_once './models/Usuario.php';
require_once './interfaces/IApiUsable.php';
require_once './clases/AutentificadorJWT.php';

class UsuarioController extends Usuario implements IApiUsable
{
  public function CargarUno($request, $response, $args)
  {
    $parametros = $request->getParsedBody();

    $usuario = new Usuario();
    $usuario->nombre = $parametros['nombre'];
    $usuario->tipo = $parametros['tipo'];
    $usuario->clave = $parametros['clave'];
    $usuario->crearUsuario();

    $response->getBody()->write(json_encode(array("mensaje" => "Usuario creado con exito")));
    return $response->withHeader('Content-Type', 'application/json');
  }

  public function TraerUno($request, $response, $args)
  {
    $id_usuario = $args['id_usuario'];
    $usuario = Usuario::obtenerUsuario($id_usuario);

    $response->getBody()->write(json_encode($usuario));
    return $response->withHeader('Content-Type', 'application/json');
  }

  public function TraerTodos($request, $response, $args)
  {
    $lista = Usuario::obtenerTodos();

    $response->getBody()->write(json_encode(array("listaUsuario" => $lista)));
    return $response->withHeader('Content-Type', 'application/json');
  }

  public function ModificarUno($request, $response, $args)
  {
    $usuarioId = $args['id'];

    $parametros = $request->getParsedBody();
    $nombre = $parametros['nombre'];
    $tipo = $parametros['tipo'];
    $clave = $parametros['clave'];

    Usuario::modificarUsuario($usuarioId, $nombre, $tipo, $clave);

    $response->getBody()->write(json_encode(array("mensaje" => "Usuario modificado con éxito")));
    return $response->withHeader('Content-Type', 'application/json');
  }

  public function BorrarUno($request, $response, $args)
  {
    $usuarioId = $args['id'];
    Usuario::borrarUsuario($usuarioId);

    $response->getBody()->write(json_encode(array("mensaje" => "Usuario borrado con exito")));
    return $response->withHeader('Content-Type', 'application/json');
  }

  public function RegistrarUsuario($request, $response)
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

    $response->getBody()->write(json_encode(['mensaje' => 'Usuario registrado']));
    return $response->withHeader('Content-Type', 'application/json');
  }

  public function Login($request, $response)
  {
    $parametros = $request->getParsedBody();
    $nombre = $parametros['nombre'];
    $clave = $parametros['clave'];

    $datosUsuario = Usuario::autenticar($nombre, $clave);

    if ($datosUsuario) {
      $token = AutentificadorJWT::CrearToken($datosUsuario);

      setcookie('token', $token);

      $response->getBody()->write(json_encode(["token" => $token]));
    } else {
      $response->getBody()->write(json_encode(["mensaje" => "Credenciales inválidas"]));
    }

    return $response->withHeader('Content-Type', 'application/json');
  }

  public function CargarArchivo($request, $response, $args)
  {
    if (isset($_FILES['archivo_csv'])) {
      $ruta = $_FILES['archivo_csv']['tmp_name'];

      $res = Usuario::cargarCSV($ruta);

      $response->getBody()->write(json_encode($res));
      return $response->withHeader('Content-Type', 'application/json');
    }

    return "Falta archivo CSV.";
  }

  public function DescargarArchivo($request, $response, $args)
  {
    Usuario::descargarCSV();

    $response->getBody()->write(json_encode(array("mensaje" => "archivo cargado con éxito")));
    return $response->withHeader('Content-Type', 'application/json');
  }
}
