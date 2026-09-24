<?php
require_once("../public/plantilla_reporte.php");
require_once("../config/conexion.php");
require_once("../models/RC.php");
require_once("../models/Vehiculo.php");
require_once("../models/Bitacora.php");
$bitacora = new Bitacora();

$recibo = new RC();
$vehiculo = new Vehiculo();
date_default_timezone_set('America/Lima');

try {
    switch ($_GET["op"]) {
        case 'imprimir':
            if (isset($_POST['tasatciudadano_id'])) {
                $tasatciudadano_ids = explode(',', $_POST['tasatciudadano_id']);
                $data = array(); // Array para almacenar los resultados

                foreach ($tasatciudadano_ids as $tasatciudadano_id) {
                    $datos = $recibo->get_datos_giro($tasatciudadano_id);

                    if ($datos) {
                        // Agregar los resultados al array de datos
                        $data[] = $datos;
                    }
                }
                
                // Unir los resultados en un solo array
                $mergedData = array_merge(...$data);
                if (!empty($mergedData)) {
                    $pdf = new PDF("P", "mm", array(117, 550));
                    $pdf->AliasNbPages();
                    $pdf->AddPage();
                    // Mostrar la fecha y hora de impresión alineada a la derecha
                    /*   $pdf->SetFont("Arial", "B", 8);
                    $dt = new DateTime('now', new DateTimeZone('America/Lima'));
                    $fechaImpresion = $dt->format('d/m/Y H:i:s');
                    $pdf->Cell(0, 4, utf8_decode("Fecha de impresión: " . $fechaImpresion), 0, 1, 'R');

                    $pdf->Ln(7); */
                    /* $pdf->Rect(5, 2, 117 - 10, $pdf->GetPageHeight() - 5); */
                    $detallesGenerales = count($datos) > 0 ? $datos[0] : null;
                    $posXImagenCentrada = ((117) / 2) - 10;
                    $pdf->SetX($posXImagenCentrada);
                    $pdf->Image('../public/logo144.png', $pdf->GetX() - 5, $pdf->GetY() - 5, 0, 30);
                    $pdf->Ln(30);
                    $pdf->SetFont("Arial", "B", 14);
                    $pdf->Cell(0, 5, utf8_decode("MUNICIPALIDAD PROVINCIAL DE CHICLAYO"), 0, 1, 'C');
                    $pdf->Ln(4);
                    $pdf->SetFont("Arial", "", 12);
                    $pdf->Cell(0, 5, "RUC: 20141784901", 0, 1, 'C');

                    $texto1 = $detallesGenerales["lomu_denominacion"] . ' ' . $detallesGenerales["lomu_direccion"];

                    $pdf->SetFont("Arial", "", 11);
                    // Multicelda para el primer texto
                    $pdf->MultiCell(0, 5, utf8_decode($texto1), 0, 'C');
                    $pdf->Ln(4);

                    $pdf->SetFont("Arial", "B", 14);
                    $pdf->Cell(0, 5, utf8_decode("Orden de Giro electrónico: " . $detallesGenerales["ogciud_id"]), 0, 1, 'C');
                    $pdf->Ln(8);
                    $pdf->SetFont("Arial", "B", 12);
                    $pdf->Cell(30, 5, utf8_decode("FECHA EMISIÓN:  "));
                    $pdf->SetFont("Arial", "", 12);
                    $pdf->SetX(42);
                    $pdf->Cell(0, 5, "    " . $detallesGenerales["fecha"] . "   " . $detallesGenerales["hora"]);
                    $pdf->SetFont("Arial", "B", 12);
                    $pdf->Ln(6);
                    $pdf->Cell(12, 5, utf8_decode("NORMA: "));
                    $pdf->SetFont("Arial", "", 12);
                    $pdf->SetX(28);
                    $pdf->Cell(0, 5, $detallesGenerales["proced_tupa"]);
                    $pdf->Ln(6);
                    $pdf->SetFont("Arial", "B", 12);
                    $pdf->Cell(12, 5, utf8_decode("AREA: "));
                    $pdf->SetFont("Arial", "", 12);
                    $areacell = utf8_decode($detallesGenerales["depe_denominacion"]);
                    $pdf->SetX(25);
                    $pdf->MultiCell(0, 5, $areacell, 0, 'L');
                    $pdf->Ln(6);
                    $pdf->SetFont("Arial", "B", 12);
                    $pdf->Cell(0, 5, utf8_decode("OP: "));
                    $pdf->SetFont("Arial", "", 12);
                    $pdf->SetX(18);
                    $pdf->MultiCell(0, 5, utf8_decode($detallesGenerales["nombre_completo"]), 0, 'L');
                    $pdf->Ln(2);
                    $pdf->Cell(0, 5, "........................................................................................");
                    $ruc = !empty($detallesGenerales["empr_ruc"])
                        ? $detallesGenerales["empr_ruc"]
                        : $detallesGenerales["empresa_ruc"];
                    if (!empty($ruc)) {
                        $direccion =  utf8_decode($detallesGenerales["empr_direccion"]);
                        $pdf->Ln(8);
                        $pdf->SetFont("Arial", "B", 12);
                        $pdf->Cell(0, 5, "RUC: ");
                        $pdf->SetX(22);
                        $pdf->SetFont("Arial", "", 12);
                        $pdf->Cell(0, 5, $detallesGenerales["empresa_ruc"]);
                        if ($detallesGenerales["empr_ruc"] != '') {
                            $pdf->Ln(5);
                            $pdf->SetFont("Arial", "B", 12);
                            $pdf->Cell(0, 5, "Razon social: ");
                            $pdf->SetX(38);
                            $pdf->SetFont("Arial", "", 12);
                            $pdf->MultiCell(0, 5, utf8_decode($detallesGenerales["empr_razon_social"]), 0, 'L');
                            $pdf->Ln(5);
                            $pdf->SetFont("Arial", "B", 12);
                            $pdf->Cell(0, 5, "Nombre Com: ");
                            $pdf->SetX(39);
                            $pdf->SetFont("Arial", "", 12);
                            $pdf->MultiCell(0, 5, utf8_decode($detallesGenerales["empr_nombre_comercial"]), 0, 'L');
                            $pdf->Ln(5);
                            $pdf->SetFont("Arial", "B", 12);
                            $pdf->Cell(0, 5, "Direccion : ");
                            $pdf->Ln(5);
                            $pdf->SetFont("Arial", "", 12);
                            $pdf->MultiCell(0, 5, $direccion, 0, 'L');
                        } else {
                            $pdf->Ln(5);
                            $pdf->SetFont("Arial", "B", 12);
                            $pdf->Cell(0, 5, "Razon social: ");
                            $pdf->SetX(38);
                            $pdf->SetFont("Arial", "", 12);
                            $pdf->MultiCell(0, 5, utf8_decode($detallesGenerales["empresa_razon_social"]), 0, 'L');
                        }
                        $pdf->Ln(2);
                        $pdf->Cell(0, 5, "........................................................................................");
                    }
                    if ($detallesGenerales["tido_id"] === 1) {
                        $pdf->Ln(5);
                        $pdf->SetFont("Arial", "B", 12);
                        $pdf->Cell(0, 5, "DNI:");
                        $pdf->SetX(20);
                        $pdf->SetFont("Arial", "", 12);
                        $pdf->Cell(0, 5, $detallesGenerales["ciudadano_dni"]);
                        $pdf->Ln(5);
                    } else if ($detallesGenerales["tido_id"] === 2) {
                        $pdf->Ln(5);
                        $pdf->SetFont("Arial", "B", 12);
                        $pdf->Cell(0, 5, "N° Pasaporte: ");
                        $pdf->SetX(42);
                        $pdf->SetFont("Arial", "", 12);
                        $pdf->Cell(0, 5, $detallesGenerales["ciudadano_dni"]);
                        $pdf->Ln(5);
                    } else if ($detallesGenerales["tido_id"] === 3) {
                        $pdf->Ln(5);
                        $pdf->SetFont("Arial", "B", 12);
                        $pdf->Cell(0, 5, "CEE: ");
                        $pdf->SetX(22);
                        $pdf->SetFont("Arial", "", 12);
                        $pdf->Cell(0, 5, $detallesGenerales["ciudadano_dni"]);
                        $pdf->Ln(5);
                    } else if ($detallesGenerales["tido_id"] === 4) {
                        $pdf->Ln(5);
                        $pdf->SetFont("Arial", "B", 12);
                        $pdf->Cell(0, 5,  utf8_decode("CARNÉ CPP: "));
                        $pdf->SetX(38);
                        $pdf->SetFont("Arial", "", 12);
                        $pdf->Cell(0, 5, $detallesGenerales["ciudadano_dni"]);
                        $pdf->Ln(5);
                    }

                    if ($direccion === '') {
                        $pdf->SetFont("Arial", "B", 12);
                        $pdf->Cell(0, 5, utf8_decode("Domicilio: "));
                        $pdf->Ln(5);
                        $pdf->SetFont("Arial", "", 12);
                        $pdf->MultiCell(0, 5, utf8_decode($detallesGenerales["ciud_domicilio_real"]), 0, 'L');
                        $pdf->Ln(5);
                    }


                    $nombreciudadano = utf8_decode("SR(A). " . $detallesGenerales["nombre_ciudadano"]);
                    $pdf->MultiCell(0, 5, $nombreciudadano, 0, 'J');
                    $pdf->Cell(0, 5, "........................................................................................");
                    $pdf->Ln(5);
                    $pdf->SetFont("Arial", "B", 12);
                    $pdf->Cell(0, 5, utf8_decode('UD. Está tramitando: '));
                    $pdf->Ln(8);
                    $pdf->SetFont("Arial", "", 12);
                    $nombreproced = utf8_decode($detallesGenerales["proced_nom"]);

                    $pdf->MultiCell(0, 5, $nombreproced, 0, 'J');
                    $total = 0;
                    $pdf->Cell(0, 5, "........................................................................................");
                    $pdf->Ln(5);
                    $pdf->SetFont("Arial", "B", 12);
                    $pdf->Cell(0, 5, utf8_decode("Tasa"));
                    $pdf->SetX(80);
                    $pdf->SetFont("Arial", "B", 12);
                    $pdf->Cell(0, 5, utf8_decode("Importe"));
                    $pdf->Ln(8);
                    $pdf->SetWidths(array(67, 30));
                    foreach ($mergedData as $detalle) {
                        $pdf->SetFont("Arial", "", 12);
                        $cantidad = isset($detalle["cantidad"]) && $detalle["cantidad"] > 1 ? $detalle["cantidad"] : 1;
                        $importeTotal = $detalle["importe"];
                        $importeFormateado = number_format($importeTotal, 2);
                        $precio = ($importeTotal == 0) ? "Gratuito" : "S/ " . $importeFormateado;

                        // Construir el nombre de la tasa con la cantidad si es mayor que 1
                        $tasaNombre = utf8_decode($detalle["tasa_nom"]);
                        if ($cantidad > 1) {
                            $tasaNombre .= " (x " . $cantidad . ")";
                        }

                        $pdf->Row2(array($tasaNombre, "  " . $precio));
                        $total += $importeTotal;
                        $pdf->Ln(2);
                    }


                    $pdf->Cell(0, 5, "........................................................................................");
                    $pdf->Ln(5);

                    $totalFormateado = number_format($total, 2);
                    if ($total == 0) {
                        $pdf->SetX(10);
                        $pdf->MultiCell(0, 5, utf8_decode("El presente recibo y el costo de este procedimiento es gratuito por las razones que sean necesarias con el número de recibo: " . $detallesGenerales["recibo"]), 0, 'C');
                    } else {
                        $pdf->SetX(67);
                        $totalFormateado = number_format($total, 2);
                        $pdf->Cell(0, 5, "Total:  S/ " . $totalFormateado, 'J');
                    }
                    $pdf->Ln(15);
                    if ($detallesGenerales["ogciud_comentario"] != "") {
                        $pdf->SetFont("Arial", "B", 12);
                        $pdf->Cell(0, 5, "Comentario: ");
                        $pdf->Ln(7);
                        $pdf->SetFont("Arial", "", 12);

                        $pdf->MultiCell(0, 5, utf8_decode($detallesGenerales["ogciud_comentario"]), 0, 'J');
                        $pdf->Ln(5);
                        $pdf->Cell(0, 5, "........................................................................................");
                        $pdf->Ln(5);
                    }

                    $pdf->SetFont("Arial", "", 12);
                    $pdf->MultiCell(0, 5, utf8_decode("¡ANTES DE SALIR, VERIFIQUE EL NOMBRE, EL MONTO Y LOS CONCEPTOS SEAN LOS CORRECTOS!"), 0, 'C');
                    $pdf->Ln(7);
                    $pdf->Output();
                } else {
                    // Manejar el caso cuando no hay datos
                    echo "No se encontraron datos para generar el PDF.";
                }
            }

            break;
        case 'imprimirxid':
            if (isset($_POST['ogciud_id'])) {

                $datos = $recibo->get_datos_giro_id($_POST['ogciud_id']);

                if (!empty($datos)) {
                    $detallesGenerales = $datos[0];
                    $pdf = new PDF("P", "mm", array(117, 550));
                    $pdf->AliasNbPages();
                    $pdf->AddPage();

                    /* $pdf->Rect(5, 2, 117 - 10, $pdf->GetPageHeight() - 5); */
                    $posXImagenCentrada = ((117) / 2) - 10;
                    $pdf->SetX($posXImagenCentrada);
                    $pdf->Image('../public/logo144.png', $pdf->GetX() - 5, $pdf->GetY() - 5, 0, 30);
                    $pdf->Ln(30);
                    $pdf->SetFont("Arial", "B", 14);
                    $pdf->Cell(0, 5, utf8_decode("MUNICIPALIDAD PROVINCIAL DE CHICLAYO"), 0, 1, 'C');
                    $pdf->Ln(4);
                    $pdf->SetFont("Arial", "", 12);
                    $pdf->Cell(0, 5, "RUC: 20141784901", 0, 1, 'C');
                    $texto1 = $detallesGenerales["lomu_denominacion"] . ' ' . $detallesGenerales["lomu_direccion"];

                    $pdf->SetFont("Arial", "", 11);
                    // Multicelda para el primer texto
                    $pdf->MultiCell(0, 5, utf8_decode($texto1), 0, 'C');

                    $pdf->Ln(4);
                    $pdf->SetFont("Arial", "B", 14);
                    $pdf->Cell(0, 5, utf8_decode("Orden de Giro electrónico: " . $detallesGenerales["ogciud_id"]), 0, 1, 'C');
                    $pdf->Ln(8);
                    $pdf->SetFont("Arial", "B", 12);
                    $pdf->Cell(30, 5, utf8_decode("FECHA EMISIÓN:  "));
                    $pdf->SetFont("Arial", "", 12);
                    $pdf->SetX(42);
                    $pdf->Cell(0, 5, "    " . $detallesGenerales["fecha"] . "   " . $detallesGenerales["hora"]);
                    $pdf->SetFont("Arial", "B", 12);
                    $pdf->Ln(6);
                    $pdf->Cell(12, 5, utf8_decode("NORMA: "));
                    $pdf->SetFont("Arial", "", 12);
                    $pdf->SetX(28);
                    $pdf->Cell(0, 5, $detallesGenerales["proced_tupa"]);
                    $pdf->Ln(6);
                    $pdf->SetFont("Arial", "B", 12);
                    $pdf->Cell(12, 5, utf8_decode("AREA: "));
                    $pdf->SetFont("Arial", "", 12);
                    $areacell = utf8_decode($detallesGenerales["depe_denominacion"]);
                    $pdf->SetX(25);
                    $pdf->MultiCell(0, 5, $areacell, 0, 'L');
                    $pdf->Ln(6);
                    $pdf->SetFont("Arial", "B", 12);
                    $pdf->Cell(0, 5, utf8_decode("OP: "));
                    $pdf->SetFont("Arial", "", 12);
                    $pdf->SetX(18);
                    $pdf->MultiCell(0, 5, utf8_decode($detallesGenerales["nombre_completo"]), 0, 'L');
                    $pdf->Ln(2);
                    $pdf->Cell(0, 5, "........................................................................................");
                    $direccion =  "";
                    $ruc = !empty($detallesGenerales["empr_ruc"])
                        ? $detallesGenerales["empr_ruc"]
                        : $detallesGenerales["empresa_ruc"];
                    if (!empty($ruc)) {
                        $direccion =  utf8_decode($detallesGenerales["empr_direccion"]);
                        $pdf->Ln(8);
                        $pdf->SetFont("Arial", "B", 12);
                        $pdf->Cell(0, 5, "RUC: ");
                        $pdf->SetX(22);
                        $pdf->SetFont("Arial", "", 12);
                        $pdf->Cell(0, 5, $ruc);
                        if ($detallesGenerales["empr_ruc"] != '') {
                            $pdf->Ln(5);
                            $pdf->SetFont("Arial", "B", 12);
                            $pdf->Cell(0, 5, "Razon social: ");
                            $pdf->SetX(38);
                            $pdf->SetFont("Arial", "", 12);
                            $pdf->MultiCell(0, 5, utf8_decode($detallesGenerales["empr_razon_social"]), 0, 'L');
                            $pdf->Ln(5);
                            $pdf->SetFont("Arial", "B", 12);
                            $pdf->Cell(0, 5, "Nombre Com: ");
                            $pdf->SetX(39);
                            $pdf->SetFont("Arial", "", 12);
                            $pdf->MultiCell(0, 5, utf8_decode($detallesGenerales["empr_nombre_comercial"]), 0, 'L');
                            $pdf->Ln(5);
                            $pdf->SetFont("Arial", "B", 12);
                            $pdf->Cell(0, 5, "Direccion : ");
                            $pdf->Ln(5);
                            $pdf->SetFont("Arial", "", 12);
                            $pdf->MultiCell(0, 5, $direccion, 0, 'L');
                        } else {
                            $pdf->Ln(5);
                            $pdf->SetFont("Arial", "B", 12);
                            $pdf->Cell(0, 5, "Razon social: ");
                            $pdf->SetX(38);
                            $pdf->SetFont("Arial", "", 12);
                            $pdf->MultiCell(0, 5, utf8_decode($detallesGenerales["empresa_razon_social"]), 0, 'L');
                        }
                        $pdf->Ln(2);
                        $pdf->Cell(0, 5, "........................................................................................");
                    }
                    if ($detallesGenerales["tido_id"] === 1) {
                        $pdf->Ln(5);
                        $pdf->SetFont("Arial", "B", 12);
                        $pdf->Cell(0, 5, "DNI:");
                        $pdf->SetX(20);
                        $pdf->SetFont("Arial", "", 12);
                        $pdf->Cell(0, 5, $detallesGenerales["ciudadano_dni"]);
                        $pdf->Ln(5);
                    } else if ($detallesGenerales["tido_id"] === 2) {
                        $pdf->Ln(5);
                        $pdf->SetFont("Arial", "B", 12);
                        $pdf->Cell(0, 5, "N° Pasaporte: ");
                        $pdf->SetX(42);
                        $pdf->SetFont("Arial", "", 12);
                        $pdf->Cell(0, 5, $detallesGenerales["ciudadano_dni"]);
                        $pdf->Ln(5);
                    } else if ($detallesGenerales["tido_id"] === 3) {
                        $pdf->Ln(5);
                        $pdf->SetFont("Arial", "B", 12);
                        $pdf->Cell(0, 5, "CEE: ");
                        $pdf->SetX(22);
                        $pdf->SetFont("Arial", "", 12);
                        $pdf->Cell(0, 5, $detallesGenerales["ciudadano_dni"]);
                        $pdf->Ln(5);
                    } else if ($detallesGenerales["tido_id"] === 4) {
                        $pdf->Ln(5);
                        $pdf->SetFont("Arial", "B", 12);
                        $pdf->Cell(0, 5,  utf8_decode("CARNÉ CPP: "));
                        $pdf->SetX(38);
                        $pdf->SetFont("Arial", "", 12);
                        $pdf->Cell(0, 5, $detallesGenerales["ciudadano_dni"]);
                        $pdf->Ln(5);
                    }

                    if ($direccion === '') {
                        $pdf->SetFont("Arial", "B", 12);
                        $pdf->Cell(0, 5, utf8_decode("Dirección: "));
                        $pdf->Ln(5);
                        $pdf->SetFont("Arial", "", 12);
                        $pdf->MultiCell(0, 5, utf8_decode($detallesGenerales["ciud_domicilio_real"]), 0, 'L');
                        $pdf->Ln(5);
                    }


                    $nombreciudadano = utf8_decode("SR(A). " . $detallesGenerales["nombre_ciudadano"]);
                    $pdf->MultiCell(0, 5, $nombreciudadano, 0, 'J');
                    $pdf->Cell(0, 5, "........................................................................................");
                    $pdf->Ln(5);
                    $pdf->SetFont("Arial", "B", 12);
                    $pdf->Cell(0, 5, utf8_decode('UD. Está tramitando: '));
                    $pdf->Ln(8);
                    $pdf->SetFont("Arial", "", 12);
                    $nombreproced = utf8_decode($detallesGenerales["proced_nom"]);

                    $pdf->MultiCell(0, 5, $nombreproced, 0, 'J');
                    $total = 0;
                    $pdf->Cell(0, 5, "........................................................................................");
                    $pdf->Ln(5);
                    $pdf->SetFont("Arial", "B", 12);
                    $pdf->Cell(0, 5, utf8_decode("Tasa"));
                    $pdf->SetX(80);
                    $pdf->SetFont("Arial", "B", 12);
                    $pdf->Cell(0, 5, utf8_decode("Importe"));
                    $pdf->Ln(8);
                    $pdf->SetWidths(array(67, 30));
                    foreach ($datos as $detalle) {
                        $pdf->SetFont("Arial", "", 12);
                        $importeFormateado = number_format($detalle["importe"], 2);

                        // Construir el nombre de la tasa con la cantidad si existe y es mayor que 1
                        $tasaNombre = utf8_decode($detalle["tasa_nom"]);
                        if (!is_null($detalle["cantidad"]) && $detalle["cantidad"] !== "" && $detalle["cantidad"] > 1) {
                            $tasaNombre .= " (x " . $detalle["cantidad"] . ")";
                        }

                        $pdf->Row2(array($tasaNombre, "  S/ " . $importeFormateado));
                        $total += $detalle["importe"];
                        $pdf->Ln(2);
                    }


                    $pdf->Cell(0, 5, "........................................................................................");
                    $pdf->Ln(5);

                    $totalFormateado = number_format($total, 2);
                    if ($total == 0) {
                        $pdf->SetX(10);
                        $pdf->MultiCell(0, 5, utf8_decode("El presente recibo y el costo de este procedimiento es gratuito por las razones que sean necesarias con el número de recibo: " . $detallesGenerales["recibo"]), 0, 'C');
                    } else {
                        $pdf->SetX(67);
                        $totalFormateado = number_format($total, 2);
                        $pdf->Cell(0, 5, "Total:  S/ " . $totalFormateado, 'J');
                    }
                    $pdf->Ln(15);
                    if ($detallesGenerales["ogciud_comentario"] != "") {
                        $pdf->SetFont("Arial", "B", 12);
                        $pdf->Cell(0, 5, "Comentario: ");
                        $pdf->Ln(7);
                        $pdf->SetFont("Arial", "", 12);

                        $pdf->MultiCell(0, 5, utf8_decode($detallesGenerales["ogciud_comentario"]), 0, 'J');
                        $pdf->Ln(5);
                        $pdf->Cell(0, 5, "........................................................................................");
                        $pdf->Ln(10);
                    }

                    $pdf->SetFont("Arial", "", 12);
                    $pdf->MultiCell(0, 5, utf8_decode("¡ANTES DE SALIR, VERIFIQUE EL NOMBRE, EL MONTO Y LOS CONCEPTOS SEAN LOS CORRECTOS!"), 0, 'C');
                    $pdf->Ln(7);
                    $pdf->Output();
                } else {
                    // Manejar el caso cuando no hay datos
                    echo "No se encontraron datos para generar el PDF.";
                }
            }

            break;

        case "listargiros":
            $datos = $recibo->get_giros($_POST['procedciudadano_id']);
            $data = array();
            foreach ($datos as $row) {
                // Obtener los valores del estado usando la función obtenerEstado
                $estado = obtenerEstado($row["est"]);

                // Crear la estructura para la lista
                $sub_array = array();
                $sub_array['ogciud_id'] = $row["ogciud_id"];
                $sub_array['recibo_nro'] = $row["recibo_nro"];
                $sub_array['fecha'] = $row["fecha"];
                $sub_array['ogciud_comentario'] = $row["ogciud_comentario"];

                // Agregar el estado y el badge
                $sub_array['estado'] = $estado['estado'];  // Nombre del estado
                $sub_array['badge'] = $estado['badge'];    // Clase de badge para el color

                // Botones de acción
                $sub_array['acciones'] = array(
                    'editar' => '<button type="button" onClick="cambiarcomentario(\'' . (string) $row['ogciud_id'] . '\');"  id="' . (string) $row['ogciud_id'] . '" class="btn btn-ghost-warning btn-sm" title="Editar"><i class="fa fa-edit"></i> Editar</button>',
                    'imprimir' => '<button type="button" onClick="imprimirGiro(\'' . (string) $row['ogciud_id'] . '\');"  id="' . (string) $row['ogciud_id'] . '" class="btn btn-ghost-danger btn-sm" title="Imprimir"><i class="fa fa-print"></i> Imprimir</button>'
                );

                // Agregar cada orden de giro al array de resultados
                $data[] = $sub_array;
            }

            // Enviar el resultado como JSON
            echo json_encode($data);
            break;


        case "listargiros_total":
            $datos = $recibo->get_giros_total($_SESSION["usu_depe_id_SIGODT"]);
            $data = array();
            foreach ($datos as $row) {
                $sub_array = array();
                $sub_array[] = $row["ogciud_id"];
                $sub_array[] = $row["orden_est"];
                $sub_array[] = $row["fecha"];
                $sub_array[] = $row["hora"];
                $sub_array[] = $row["ciudadano_dni"];
                $sub_array[] = $row["nombre_ciudadano"];
                $sub_array[] = $row["empr_ruc"];
                $sub_array[] = $row["total_monto"];
                $sub_array[] = $row["ogciud_comentario"];

                // Verifica si el estado es igual a 1 para habilitar o deshabilitar el botón de edición de comentario
                if ($row["orden_est"] == 1) {
                    $sub_array[] = '<button type="button" style="cursor:pointer" onClick="cambiarcomentario(\'' . (string) $row['ogciud_id'] . '\');"  id="' . (string) $row['ogciud_id'] . '" class="btn btn-outline-warning btn-icon"><div><i class="fa fa-edit"></i></div></button>';
                } else {
                    $sub_array[] = '<button type="button"  disabled class="btn btn-outline-warning btn-icon"><div><i class="fa fa-edit"></i></div></button>';
                }
                if ($row["orden_est"] != 0) {
                    $sub_array[] = '<button type="button"style="cursor:pointer" onClick="imprimirGiro(\'' . (string) $row['ogciud_id'] . '\');"  id="' . (string) $row['ogciud_id'] . '" class="btn btn-outline-danger btn-icon"><div><i class="fa fa-print"></i></div></button>';
                } else {
                    $sub_array[] = '<button type="button" disabled class="btn btn-outline-warning btn-icon"><div><i class="fa fa-print"></i></div></button>';
                }


                $data[] = $sub_array;
            }

            $results = array(
                "sEcho" => 1,
                "iTotalRecords" => count($data),
                "iTotalDisplayRecords" => count($data),
                "aaData" => $data
            );

            echo json_encode($results);
            break;
        case "cambiarComentario":
            $recibo->cambiar_comentario($_POST['comentario'], (string)$_POST['ogciud_id']);
            $bitacora->update_bitacora($_SESSION["usua_id_SIGODT"]);
            break;
    }
} catch (Exception $e) {
    // Manejar errores aquí (por ejemplo, registrarlos o devolver un mensaje de error al ciudadano)
    echo "Error al generar el PDF: " . $e->getMessage();
}


function obtenerEstado($estadoInput)
{
    switch ($estadoInput) {
        case 0:
            return [
                'estado' => 'Anulado',
                'badge' => 'bg-red-lt' // Rojo
            ];
        case 1:
            return [
                'estado' => 'Pendiente',
                'badge' => 'bg-yellow-lt' // Amarillo
            ];
        case 2:
            return [
                'estado' => 'Girado',
                'badge' => 'bg-green-lt' // Verde
            ];
        case 3:
            return [
                'estado' => 'Improcedente',
                'badge' => 'bg-red-lt' // Rojo
            ];
        case 4:
            return [
                'estado' => 'Pagado',
                'badge' => 'bg-blue-lt' // Azul
            ];
        case 5:
            return [
                'estado' => 'Completado',
                'badge' => 'bg-purple-lt' // Morado
            ];
        default:
            return [
                'estado' => 'Extornado',
                'badge' => 'bg-orange-lt' // Naranja
            ];
    }
}
