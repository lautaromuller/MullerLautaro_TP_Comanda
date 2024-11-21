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
    $usuario->sector = $parametros['sector'];
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
    $sector = $parametros['sector'];
    $clave = $parametros['clave'];

    Usuario::modificarUsuario($usuarioId, $nombre, $sector, $clave);

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
    $sector = $parametros['sector'];
    $clave = $parametros['clave'];

    $usr = new Usuario();
    $usr->nombre = $nombre;
    $usr->sector = $sector;
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
      if(empty($datosUsuario["fecha_baja"])){
        $token = AutentificadorJWT::CrearToken($datosUsuario);
        $response->getBody()->write(json_encode(['mensaje' => $token]));
      } else {
        $response->getBody()->write(json_encode(["mensaje" => "El usuario está dado de baja"]));
      }
    } else {
      $response->getBody()->write(json_encode(["mensaje" => "Credenciales inválidas"]));
    }

    return $response->withHeader('Content-Type', 'application/json');
  }

  public function CargarArchivo($request, $response, $args)
  {
    if (isset($_FILES['archivo_csv'])) {
      $ruta = $_FILES['archivo_csv']['tmp_name'];

      Usuario::cargarCSV($ruta);

      $response->getBody()->write(json_encode(array("mensaje" => "archivo cargado con éxito")));
      return $response->withHeader('Content-Type', 'application/json');
    }

    $response->getBody()->write(json_encode(array("mensaje" => "Falta archivo CSV")));
    return $response->withHeader('Content-Type', 'application/json');
  }

  public function DescargarArchivo($request, $response, $args)
  {
    Usuario::descargarCSV();

    $response->getBody()->write(json_encode(array("mensaje" => "archivo descargado con éxito")));
    return $response->withHeader('Content-Type', 'application/json');
  }

  public function CargarEncuesta($request, $response, $args)
  {
    $parametros = $request->getParsedBody();
    $p_mesa = $parametros['puntaje_mesa'];
    $p_restaurante = $parametros['puntaje_restaurante'];
    $p_mozo = $parametros['puntaje_mozo'];
    $p_cocinero = $parametros['puntaje_cocinero'];
    $critica = $parametros['critica'] ?? null;

    if($p_mesa && $p_restaurante && $p_mozo && $p_cocinero){
      Usuario::subirEncuesta($p_mesa,$p_restaurante,$p_mozo,$p_cocinero,$critica);
      $response->getBody()->write(json_encode("Encuesta subida con éxito"));
    } else {
      $response->getBody()->write(json_encode("Faltan datos en la encuesta"));
    }
    return $response->withHeader('Content-Type', 'application/json');
  }
}