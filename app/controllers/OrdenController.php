<?php
require_once './models/Orden.php';
require_once './interfaces/IApiUsable.php';
require_once __DIR__ . '/../../vendor/autoload.php';

class OrdenController extends Orden implements IApiUsable
{
    public function cargarUno($request, $response, $args)
    {
        $parametros = $request->getParsedBody();
        $files = $request->getUploadedFiles();
        $nombreCliente = $parametros['nombre_cliente'];
        $mesaId = $parametros['codigo_mesa'];
        $productos = json_decode($parametros['productos'], true);
        $foto = isset($files['foto']) ? $files['foto'] : null;

        if (isset($foto)) {
            $dir = __DIR__ . "/../imagenes/";
            if (!file_exists($dir)) {
                mkdir($dir, 0777, true);
            }

            $nombreArchivo = $nombreCliente . "_" . $mesaId;
            $ruta = $dir . $nombreArchivo;

            $foto->moveTo($ruta);
            $foto = $nombreArchivo;
        } else {
            $foto = null;
        }

        $mesa = Mesa::obtenerMesa($mesaId);
        if ($mesa->estado == "cerrada") {
            $payload = json_encode(array("mensaje" => "La mesa está cerrada"));
            $response->getBody()->write($payload);
            return $response->withHeader('Content-Type', 'application/json');
        }

        $orden = new Orden();
        $orden->codigo_mesa = $mesaId;
        $orden->nombre_cliente = $nombreCliente;
        $orden->foto = $foto;
        $resultado = $orden->crearOrden($productos);

        $payload = json_encode(array("mensaje" => "Orden creada con éxito", "codigo" => $resultado, "mesa" => $mesaId));
        $response->getBody()->write($payload);
        return $response->withHeader('Content-Type', 'application/json');
    }

    public function TraerTodos($request, $response, $args)
    {
        $lista = Orden::obtenerTodos();

        $payload = json_encode(array("listaOrdenes" => $lista));

        $response->getBody()->write($payload);
        return $response->withHeader('Content-Type', 'application/json');
    }

    public function TraerPendientes($request, $response, $args)
    {
        $sector = $request->getAttribute('sector_usuario');
        $lista = Orden::obtenerTodos();

        $listaPendientes = array();
        foreach ($lista as $orden) {
            $pendientes = Orden::obtenerPendientes($orden->codigo_pedido, $sector);
            if ($pendientes) {
                array_push($listaPendientes, $pendientes);
            }
        }

        $response->getBody()->write(json_encode(array("listaPendientes" => $listaPendientes)));
        return $response->withHeader('Content-Type', 'application/json');
    }

    public function TraerUno($request, $response, $args)
    {
        $codigo_pedido = $args['codigo_pedido'];
        $codigo_mesa = $args['codigo_mesa'];
        $orden = Orden::obtenerOrden($codigo_pedido, $codigo_mesa);

        $payload = json_encode(array("orden" => $orden));

        $response->getBody()->write($payload);
        return $response->withHeader('Content-Type', 'application/json');
    }

    public function ModificarUno($request, $response, $args)
    {
        $codigo_pedido = $args['codigo_pedido'];
        $sector = $request->getAttribute('sector_usuario');
        $parametros = $request->getParsedBody();

        if (strlen($codigo_pedido) == 5) {
            if ($sector == 'mozo') {
                $nombreCliente = $parametros['nombre'];
                $productos = $parametros['productos'];
                Orden::modificarDatosOrden($codigo_pedido, $nombreCliente, $productos);
            } else if ($sector == 'cocina' || $sector == 'cerveceria' || $sector == 'bar') {
                $estado_pedido = strtolower($parametros['estado_pedido']);
                if ($estado_pedido == "listo para servir" || $estado_pedido == "en preparación") {
                    Orden::modificarEstadoOrden($codigo_pedido, $sector, $estado_pedido);
                } else {
                    $response->getBody()->write(json_encode(array("mensaje" => "Estado no válido")));
                    return $response->withHeader('Content-Type', 'application/json');
                }
            } else {
                $response->getBody()->write(json_encode(array("mensaje" => "Acesso denegado")));
                return $response->withHeader('Content-Type', 'application/json');
            }
        } else {
            $response->getBody()->write(json_encode(array("error" => "El código de pedido no puede estar vacío")));
            return $response->withHeader('Content-Type', 'application/json');
        }

        $response->getBody()->write(json_encode(array("mensaje" => "Orden modificada con éxito")));
        return $response->withHeader('Content-Type', 'application/json');
    }

    public function BorrarUno($request, $response, $args)
    {
        $codigo_pedido = $args['codigo_pedido'];
        Orden::borrarOrden($codigo_pedido);
        $payload = json_encode(array("mensaje" => "órden borrada con éxito"));

        $response->getBody()->write($payload);
        return $response->withHeader('Content-Type', 'application/json');
    }

    public function CargarArchivo($request, $response, $args)
    {
        if (isset($_FILES['archivo_csv'])) {
            $ruta = $_FILES['archivo_csv']['tmp_name'];

            $res = Orden::cargarCSV($ruta);

            $response->getBody()->write(json_encode($res));
            return $response->withHeader('Content-Type', 'application/json');
        }

        return "Falta archivo CSV.";
    }

    public function DescargarArchivo($request, $response, $args)
    {
        Orden::descargarCSV();

        $response->getBody()->write(json_encode(array("mensaje" => "archivo cargado con éxito")));
        return $response->withHeader('Content-Type', 'application/json');
    }

    public function VerTiempoPedido($request, $response, $args)
    {
        $codigo_pedido = $args['codigo_pedido'];
        $codigo_mesa = $args['codigo_mesa'];

        $orden = Orden::verTiempoOrden($codigo_pedido, $codigo_mesa);

        $horaInicio = new DateTime($orden->inicio_preparacion);
        $horaActual = new DateTime();

        $diferencia = $horaInicio->diff($horaActual);
        $tiempo = ($diferencia->h * 60) + $diferencia->i;

        if ($orden->tiempo > $tiempo) {
            $orden->tiempo = $tiempo;
        } else {
            $orden->tiempo = 0;
        }

        $response->getBody()->write(json_encode(array("Tiempo estimado" => $orden->tiempo)));
        return $response->withHeader('Content-Type', 'application/json');
    }

    public function descargarArchivoPDF()
    {
        $pdf = new \TCPDF();
        $pdf->AddPage();

        $pdf->SetFont('helvetica', 'B', 16);
        $pdf->Cell(0, 10, 'Lista de Ordenes', 0, 1, 'C');

        $ordenes = Orden::obtenerTodos();

        $pdf->SetFont('helvetica', '', 14);

        foreach ($ordenes as $key => $orden) {
            $pdf->Cell(0, 10, 'Orden N°' . $key + 1, 0, 1);
            $pdf->Cell(0, 10, 'Nombre Cliente: ' . $orden->nombre_cliente, 0, 1);
            $pdf->Cell(0, 10, 'Código Mesa: ' . $orden->codigo_mesa, 0, 1);
            $pdf->Cell(0, 10, 'Código Pedido: ' . $orden->codigo_pedido, 0, 1);
            $pdf->Cell(0, 10, 'Estado Mesa: ' . $orden->estado_mesa, 0, 1);
            $pdf->Cell(0, 10, 'Estado Pedido: ' . $orden->estado_pedido, 0, 1);


            $rutaImg = __DIR__ . "/../imagenes/" . $orden->foto;

            if (file_exists($rutaImg)) {
                $pdf->Image($rutaImg, 115, $pdf->GetY() - 57, 80, 60);
                $pdf->Ln(55);
            }

            $pdf->Ln(10);
        }
        $pdf->Output('ordenes_' . date("d-m-Y") . '.pdf', 'D');
    }
}
