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

    $response->getBody()->write(json_encode(array("lista Usuario" => $lista)));
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
    $accion = $args['accion'];
    if($accion == "suspendido"){
      Usuario::borrarUsuario($usuarioId, $accion);
      $response->getBody()->write(json_encode(array("mensaje" => "Usuario suspendido")));
    } else if($accion == "dado de baja"){
      Usuario::borrarUsuario($usuarioId, $accion);
      $response->getBody()->write(json_encode(array("mensaje" => "Usuario dado de baja")));
    } else{
      $response->getBody()->write(json_encode(array("mensaje" => "No se pudo borrar el usuario. Acción no válida")));
    }

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
      if (empty($datosUsuario["fecha_baja"])) {
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
    $c_mesa = $parametros['codigo_mesa'];
    $c_pedido = $parametros['codigo_pedido'];
    $p_mesa = $parametros['puntaje_mesa'];
    $p_restaurante = $parametros['puntaje_restaurante'];
    $p_mozo = $parametros['puntaje_mozo'];
    $p_cocinero = $parametros['puntaje_cocinero'];
    $mensaje = $parametros['mensaje'] ?? null;

    if ($mensaje != null && strlen($mensaje) > 66) {
      $response->getBody()->write(json_encode("El mensaje no puede tener más de 66 caracteres"));
      return $response->withHeader('Content-Type', 'application/json');
    }

    $orden = Orden::obtenerOrden($c_pedido);
    if (isset($orden) && $p_mesa && $p_restaurante && $p_mozo && $p_cocinero) {
      if(!Usuario::existeEncuesta($c_pedido)){
        Usuario::subirEncuesta($p_mesa, $p_restaurante, $p_mozo, $p_cocinero, $mensaje, $c_mesa, $c_pedido);
        $response->getBody()->write(json_encode("Encuesta subida con éxito"));
      }
      else{
        $response->getBody()->write(json_encode("La encuesta ya fue subida"));
      }
    } else {
      $response->getBody()->write(json_encode("Faltan datos en la encuesta"));
    }
    return $response->withHeader('Content-Type', 'application/json');
  }

  public function VerComentarios($request, $response, $args)
  {
    $lista = Usuario::mejoresComentarios();

    $response->getBody()->write(json_encode(array("mejores comentarios" => $lista)));
    return $response->withHeader('Content-Type', 'application/json');
  }

  
}
