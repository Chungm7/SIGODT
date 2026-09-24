<?php
require_once("../public/plantilla_reporte.php");
require_once("../config/conexion.php");
require_once("../models/PortadaProced.php");
require_once("../models/Bitacora.php");
$bitacora = new Bitacora();

$portada = new PortadaProced();

try {
    switch ($_GET["op"]) {
        case 'imprimir':
            if (isset($_POST['procedciudadano_id'])) {
                $datos = $portada->get_datos_portada($_POST['procedciudadano_id']);
                if (!empty($datos)) {
                    $pdf = new PDF("P", "mm", "A4");

                    $pdf->AliasNbPages();
                    $pdf->AddPage();
                    $pdf->SetLeftMargin(20);
                    $pdf->SetFont("Arial", "", 8);
                    $pdf->SetX(2);
                    // Imagen y nombre de la organización en una celda
                    $pdf->Cell(10, 20, '', 0); // Celda vacía para alinear la imagen
                    $pdf->Image('../public/logo144.png', $pdf->GetX() + 15, $pdf->GetY() + 2, 0, 20);
                    $pdf->SetY(32);
                    $pdf->SetX(15);

                    $PROCEDIMIENTO = utf8_decode("MUNICIPALIDAD PROVINCIAL DE CHICLAYO");
                    $pdf->MultiCell(45, 5, $PROCEDIMIENTO, 0, 'C');
                    $pdf->SetX(15);
                    $PROCEDIMIENTO = utf8_decode($datos[0]['depe_denominacion']);
                    $pdf->MultiCell(45, 5, $PROCEDIMIENTO, 0, 'C');
                    $pdf->SetFont("Arial", "", 10);

                    $pdf->SetY(12);
                    $pdf->SetX(30);
                    $pdf->Cell(190, 20, utf8_decode("FORMULARIO DE TRÁMITE - SUB GERENCIA DE TRANSPORTE"), 0, 1, 'C');
                    $pdf->SetLineWidth(0.2);
                    $pdf->SetX(60);
                    $pdf->Line($pdf->GetX(), $pdf->GetY(), $pdf->GetX() + 140, $pdf->GetY());
                    $pdf->Ln(2);
                    $pdf->SetX(60);

                    $pdf->SetFont("Arial", "B", 10);
                    $PROCEDIMIENTO = utf8_decode("PROCEDIMIENTO: " . $datos[0]['proced_nom']);
                    $pdf->MultiCell(0, 5, $PROCEDIMIENTO, 0, 'J');
                    $pdf->SetFont("Arial", "B", 10);

                    $pdf->SetX(60);
                    // Agregar una línea en la parte superior del PDF
                    $pdf->SetLineWidth(0.2);
                    $pdf->Line(10, 10, 200, 10);
                    $pdf->Ln(12);
                    // Crear la tabla con los datos
                    $pdf->SetFont('Arial', '', 11);

                    // Nombre
                    $pdf->Cell(60, 7, 'NOMBRE: (*) ', 1, 0);
                    $pdf->Cell(120, 7, $datos[0]['nombre_completo'], 1, 1);

                    // DNI
                    $pdf->Cell(60, 7, 'Doc Identidad: (*)', 1, 0);
                    $pdf->Cell(120, 7, $datos[0]['ciud_numero_documento'], 1, 1);

                    // Domicilio
                    $pdf->Cell(60, 7, 'DOMICILIO: (*)', 1, 0);
                    $pdf->Cell(120, 7, '', 1, 1);

                    // Placa
                    $pdf->Cell(60, 7, 'PLACA: (*)', 1, 0);
                    if ($datos[0]['proced_tipoindvasc'] == "2") {
                        $pdf->Cell(120, 7, $datos[0]['vehi_placa'], 1, 1);
                    } else {
                        $pdf->Cell(120, 7, '', 1, 1);
                    }

                    // Empresa o Asociación
                    $pdf->Cell(60, 7, utf8_decode('EMPRESA O ASOCIACIÓN: (*)'), 1, 0);
                    $pdf->Cell(120, 7, '', 1, 1);
                    $pdf->Cell(60, 7, utf8_decode('N° RECIBO DE PAGO: (*)'), 1, 0);
                    $pdf->Cell(120, 7, $datos[0]['procedciudadano_npago'], 1, 1);
                    $pdf->Ln(4);
                    $pdf->SetFont("Arial", "B", 10);
                    $pdf->Cell(0, 5, utf8_decode("REQUISITOS"), 0, 1, 'C');
                    $pdf->Ln(4);


                    $datos_requisitos = $portada->get_datos_requerimito_procedid($datos[0]['proced_id']);
                    if (!empty($datos_requisitos)) {
                        $pdf->SetFont('Arial', '', 11);
                        $contador = 1;
                        $pdf->SetWidths(array(10, 160, 10));
                        foreach ($datos_requisitos as $requisito) {
                            $requisito_texto = utf8_decode($requisito['req_nom']);
                            $pdf->Row(array($contador, $requisito_texto, ''));
                            $contador++;
                        }
                    } else {
                        // Manejar el caso cuando no hay requisitos
                        $pdf->Cell(190, 10, utf8_decode('No hay requisitos para este trámite.'), 0, 1, 'C');
                    }
                    $pdf->Ln(4);
                    $pdf->SetFont("Arial", "B", 10);

                    $pdf->Cell(0, 5, utf8_decode("DECLARACION JURADA"), 0, 1, 'C');
                    $pdf->Ln(4);
                    $pdf->SetFont("Arial", "", 10);
                    $declaracion = utf8_decode("Declaro bajo juramento que la información y documentación que he proporcionado es verdadera y cumple con los requisitos exigidos, caso contrario el acto administrativo será nulo de acuerdo con el TUO (Texto Único Ordenado) según el art. 10° de la Ley N.º 27444 (Ley de Procedimientos Administrativos) y teniendo conocimiento que, de haber presentado información falsa, se me aplicará las sanciones administrativas y/o penales establecidos por ley.");
                    $pdf->MultiCell(0, 5, $declaracion, 'LTRB', 'J');
                    $pdf->SetLineWidth(0.2);
                    $fechaCompleta = $datos[0]['fechacrea'];
                    $fechaSeparada = explode(" ", $fechaCompleta);
                    $fecha = $fechaSeparada[0]; // Obtenemos la parte de la fecha sin la hora

                    // Convertimos la fecha en un array separando año, mes y día
                    $fechaArray = explode("-", $fecha);
                    $año = $fechaArray[0];
                    $mes = $fechaArray[1];
                    $dia = $fechaArray[2];

                    // Definimos un array para los nombres de los meses en español
                    $meses = array("enero", "febrero", "marzo", "abril", "mayo", "junio", "julio", "agosto", "septiembre", "octubre", "noviembre", "diciembre");

                    // Formateamos la fecha con el nombre del mes en lugar del número
                    $mesTexto = $meses[$mes - 1]; // Restamos 1 porque los arrays en PHP son base 0

                    // Ahora puedes usar $dia, $mesTexto y $año en tu multicelda
                    $pdf->MultiCell(0, 6, 'Chiclayo, ' . $dia . ' de ' . $mesTexto . ' del ' . $año, 'LTRB', 'R');

                    $pdf->Ln(0);
                    $yn = $pdf -> GetY();
                    $pdf->Cell(0, 30, '.......................................', 'LTRB', 1, 'C');
                    $pdf-> SetX(50);
                    $pdf-> SetY($yn+17);
                    $pdf->Cell(0, 5, 'Firma', 0, 1, 'C'); // La última célula no tiene bordes en la parte inferior



                    // Salida del PDF
                    $pdf->Output();
                } else {
                    // Manejar el caso cuando no hay datos
                    echo "No se encontraron datos para generar el PDF.";
                }
            }
            break;
        case 'get_npago':
            $datos = $portada->get_npago($_POST["procedciudadano_id"]);
            if (is_array($datos) && count($datos) > 0) {
                foreach ($datos as $row) {
                    $output["procedciudadano_npago"] = $row["procedciudadano_npago"];
                }
                echo json_encode($output);
            } else {
                echo json_encode(null);
            }
            break;
        case 'actualizarNpago':
            $datos = $portada->actualizar_npago($_POST["numero"], $_POST["procedciudadano_id"]);
            $bitacora->update_bitacora($_SESSION["usua_id_SIGODT"]);
            break;
        case 'imprimirInfo':
            $datos = $portada->get_datos_info($_POST['proced_id']);
            if (!empty($datos)) {
                $pdf = new PDF("P", "mm", "A5");

                $pdf->AliasNbPages();
                $pdf->AddPage();
                $pdf->SetFont("Arial", "", 5);
                $pdf->SetX(2);
                // Imagen y nombre de la organización en una celda
                $pdf->Cell(10, 20, '', 0); // Celda vacía para alinear la imagen
                $pdf->Image('../public/logo144.png', $pdf->GetX() + 10, $pdf->GetY() + 2, 0, 20);
                $pdf->SetY(32);
                $PROCEDIMIENTO = utf8_decode("MUNICIPALIDAD PROVINCIAL DE CHICLAYO");
                $pdf->MultiCell(45, 2, $PROCEDIMIENTO, 0, 'C');

                $PROCEDIMIENTO = utf8_decode($datos[0]['depe_denominacion']);
                $pdf->MultiCell(45, 2, $PROCEDIMIENTO, 0, 'C');
                $pdf->SetFont("Arial", "", 10);

                $pdf->SetY(12);
                $pdf->SetX(60);
                $PROCEDIMIENTO = utf8_decode("FICHA INFORMATIVA" . " - " . $datos[0]['depe_denominacion']);
                $pdf->MultiCell(0, 5, $PROCEDIMIENTO, 0, 'J');
                $pdf->SetLineWidth(0.2);
                $pdf->SetX(60);
                $pdf->Line($pdf->GetX(), $pdf->GetY(), $pdf->GetX() + 140, $pdf->GetY());
                $pdf->Ln(2);
                $pdf->SetX(60);

                $pdf->SetFont("Arial", "B", 8);
                $PROCEDIMIENTO = utf8_decode("PROCEDIMIENTO: " . $datos[0]['proced_nom']);
                $pdf->MultiCell(0, 5, $PROCEDIMIENTO, 0, 'J');
                $pdf->SetFont("Arial", "B", 8);

                $pdf->SetX(60);
                // Agregar una línea en la parte superior del PDF
                $pdf->SetLineWidth(0.2);
                $pdf->Line(10, 10, 200, 10);
                $pdf->Ln(7);
                // Crear la tabla con los datos

                $pdf->SetFont("Arial", "", 11);
                $pdf->Cell(0, 5, utf8_decode("TASAS:"), 0, 1, 'C');
                $pdf->Ln(2);

                $datos_tasas = $portada->get_tasas_info($_POST["proced_id"]);
                if (!empty($datos_tasas)) {
                    $pdf->SetFont('Arial', '',10);
                    $pdf->SetWidths(array(10, 100, 20));
                    foreach ($datos_tasas as $tasa) {
                        $tasa_text = utf8_decode($tasa['tasa_nom']);
                        $importeFormateado = number_format($tasa['tasaproced_monto'], 2);
                        $pdf->Row(array($tasa['tasaproced_pos'], $tasa_text, 'S/ '. $importeFormateado));
                    }
                } else {
                    // Manejar el caso cuando no hay requisitos
                    $pdf->Cell(190, 10, utf8_decode('No hay requisitos para este trámite.'), 0, 1, 'C');
                }
                $pdf->Ln(4);
                $pdf->SetFont("Arial", "", 10);
                $pdf->Cell(0, 5, utf8_decode("REQUISITOS:"), 0, 1, 'C');
                $pdf->Ln(1);

                $datos_requisitos = $portada->get_datos_requerimito_procedid($_POST["proced_id"]);
                if (!empty($datos_requisitos)) {
                    $pdf->SetFont('Arial', '', 8.5);
                    $contador = 1;
                    $pdf->SetWidths(array(10, 110, 10));
                    foreach ($datos_requisitos as $requisito) {
                        $requisito_texto = utf8_decode($requisito['req_nom']);
                        $pdf->Row(array($contador, $requisito_texto, ''));
                        $contador++;
                    }
                } else {
                    // Manejar el caso cuando no hay requisitos
                    $pdf->Cell(190, 10, utf8_decode('No hay requisitos para este trámite.'), 0, 1, 'C');
                }
               
                // Salida del PDF
                $pdf->Output();
            } else {
                // Manejar el caso cuando no hay datos
                echo "No se encontraron datos para generar el PDF.";
            }
            break;
    }
} catch (Exception $e) {
    // Manejar errores aquí (por ejemplo, registrarlos o devolver un mensaje de error al ciudadano)
    echo "Error al generar el PDF: " . $e->getMessage();
}
