<?php
class usuario extends conectar
{
    public function login()
    {
        $conectar = parent::conexion();
        parent::set_names();
        if (isset($_POST["enviar"])) {
            $dni = $_POST["usu_dni"];
            $pass = $_POST["usu_pass"];
            if (empty($dni) and empty($pass)) {
                header("Location:" . conectar::ruta() . "index.php?m=2");
                exit();
            } else {

                $ip = $_SERVER['REMOTE_ADDR'];
                if (strpos($ip, '::') === 0) {
                    // Si es IPv6, intenta obtener la dirección IPv4 local
                    $ip = '::1'; // Dirección IPv6 de localhost

                    // REEMPLAZAR POR LA DIRECCIÓN IP DE LA PC
                    $ip = "192.168.12.44";
                }

                //comienzo API seguridad
                $ch = curl_init();
                $ws_reniec = "http://10.10.10.16/sisSeguridad/ws/ws.php/?op=login&pers_dni=" . $dni . "&pers_contrasena=" . $pass . "&pers_ip=" . $ip . "&sist_inic=SIGI";

                curl_setopt($ch, CURLOPT_URL, $ws_reniec);
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                $response = curl_exec($ch);

                if (curl_errno($ch)) {
                    $error_msg = curl_error($ch);
                    echo json_encode(array('error' => 'Error al conectarse al servicio' . $error_msg));
                    exit(); // Interrumpe la ejecución si hay error en cURL
                } else {
                    curl_close($ch);
                    $data = json_decode($response, true);
                    if ($data === null) {
                        echo json_encode(array('error' => 'Respuesta inválida del servicio'));
                        exit();
                    }
                }
                //fin API

                //La respuesta me la trae así:
                //{"status":404,"detalle":"Datos incorrectos"}
                //Guardar en una variable el detalle
                $detalle = $data["detalle"];

                if ($detalle == "No se encontraron datos") {
                    header("Location:" . Conectar::ruta() . 'index.php?m=3');
                    exit();
                } elseif ($detalle == "IP Persona no registrada") {
                    header("Location:" . Conectar::ruta() . 'index.php?m=4');
                    exit();
                } elseif ($detalle == "Persona inactiva") {
                    header("Location:" . Conectar::ruta() . 'index.php?m=5');
                    exit();
                } elseif ($detalle == "Fuera de la hora de acceso") {
                    header("Location:" . Conectar::ruta() . 'index.php?m=6');
                    exit();
                } elseif ($detalle == "Usuario no vigente") {
                    header("Location:" . Conectar::ruta() . 'index.php?m=7');
                    exit();
                } elseif ($detalle == "Datos incorrectos") {
                    header("Location:" . Conectar::ruta() . 'index.php?m=8');
                    exit();
                }

                // Verificar si la decodificaciÃ³n fue exitosa
                if ($data !== null) {

                    $_SESSION["usua_id_SIGODT"] = $data["pers_id"];
                    $_SESSION["usua_dni_SIGODT"] = $data["pers_dni"];
                    $_SESSION["rol_id_SIGODT"] = $data["perf_id"];
                    $_SESSION["usua_estado_SIGODT"] = $data["pers_estado"];
                    $_SESSION["historial_login_SIGODT"] = $data["hise_id"];
                    $_SESSION["pers_apellidos_SIGODT"] = $data["pers_apelpat"] . " " . $data["pers_apelmat"];
                    $_SESSION["pers_nombre_SIGODT"] = $data["pers_nombre"];
                    $_SESSION["nombre_completo_SIGODT"] = $data["pers_nombre"] . " " . $data["pers_apelpat"] . " " . $data["pers_apelmat"];
                    $_SESSION["rol_nombre_SIGODT"] = $data["perf_nombre"];
                    $_SESSION["pers_emailm_SIGODT"] = $data["pers_emailm"];
                    $_SESSION["pers_celu01_SIGODT"] = $data["pers_celu01"];
                    $_SESSION["historial_login_SIGODT"] = $data["hise_id"];
                    $_SESSION["usu_depe_id_SIGODT"] = $data["depe_id"];

                    switch ($_SESSION["rol_id_SIGODT"]) {

                        case 8:
                            header("Location: " . Conectar::ruta() . "view/giros_ciudadano/");
                            exit();
                        case 9:

                            //header("Location:" . Conectar::ruta() . "views/AdminDashboard/");
                            header("Location:" . Conectar::ruta() . "view/giros_ciudadano/");
                            exit();
                    }
                } else {
                    header("Location:" . Conectar::ruta() . 'index.php?m=1');
                    exit();
                }
            }
        }
    }
    public function logout($hise_id)
    {
        $conectar = parent::conexion();
        parent::set_names();
        $hise_id = $hise_id;

        //comienzo API seguridad
        $ch = curl_init();
        $ws_reniec = "http://10.10.10.16/sisSeguridad/ws/ws.php/?op=logout&hise_id=" . $hise_id;

        curl_setopt($ch, CURLOPT_URL, $ws_reniec);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $response = curl_exec($ch);

        if (curl_errno($ch)) {
            $error_msg = curl_error($ch);
            echo json_encode(array('error' => 'Error al conectarse al servicio'));
        } else {
            curl_close($ch);
        }

        session_destroy();
        header("Location:" . Conectar::ruta() . "index.php");
        exit();
    }

    public function cambiar_contrasena_API($pers_id, $claveantigua, $clave, $clave2)
    {
        $conectar = parent::conexion();
        parent::set_names();

        //comienzo API seguridad
        $ch = curl_init();
        $ws_reniec = "http://10.10.10.16/sisSeguridad/ws/ws.php/?op=cambiar_contrasena&pers_id=" . $pers_id . "&claveantigua=" . $claveantigua . "&clave=" . $clave . "&clave2=" . $clave2;

        curl_setopt($ch, CURLOPT_URL, $ws_reniec);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $response = curl_exec($ch);

        if (curl_errno($ch)) {
            $error_msg = curl_error($ch);
            echo json_encode(array('error' => 'Error al conectarse al servicio'));
        } else {
            curl_close($ch);
            // Decodificar la respuesta JSON en un array asociativo
            $data = $response;
        }

        return $data;
    }
    public function cambiar_estado_Persona($pers_id, $pers_estado)
    {
        $conectar = parent::conexion();
        parent::set_names();
        $sql = "UPDATE sc_escalafon.tb_persona SET pers_estado = ? WHERE pers_id = ?;";
        $sql = $conectar->prepare($sql);
        $sql->bindValue(1, $pers_estado);
        $sql->bindValue(2, $pers_id);
        $sql->execute();
        return $resultado = $sql->fetchAll();
    }

    //Funcion para buscar un Persona por su id, usando la siguiente funcion:
    public function get_Persona_por_id($pers_id)
    {
        $conectar = parent::conexion();
        parent::set_names();
        $sql = "SELECT * FROM sc_escalafon.tb_persona 
        INNER JOIN sc_escalafon.tb_estado_civil ON sc_escalafon.tb_persona.esci_id = sc_escalafon.tb_estado_civil.esci_id
        WHERE pers_id = ?;";
        $sql = $conectar->prepare($sql);
        $sql->bindValue(1, $pers_id);
        $sql->execute();
        return $resultado = $sql->fetchAll();
    }
    public function listarSistemasUsuario(int $usu_id): array
    {
        // Conexión y configuración de nombres
        $conectar = parent::conexion();
        parent::set_names();

        // Consulta: DISTINCT para no repetir sistemas, corrigiendo el JOIN
        $sql = "
            SELECT DISTINCT
                sis.sist_id,
                sis.sist_denominacion,
                sis.sist_iniciales,
                sis.sist_logo,sist_url
            FROM sc_seguridad.tb_permiso AS prm
            INNER JOIN sc_seguridad.tb_sistema AS sis
                ON prm.sist_id = sis.sist_id
            WHERE prm.pers_id     = ?
              AND prm.perm_estado = 'A'
        ";

        $stmt = $conectar->prepare($sql);
        $stmt->bindValue(1, $usu_id, PDO::PARAM_INT);
        $stmt->execute();

        // Devolver todos los registros como array asociativo
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    //Funcion para buscar una persona por el documento de identidad, usando la siguiente funcion:
    public function get_persona_by_dni($dni)
    {
        $conectar = parent::conexion();
        parent::set_names();
        $sql = "SELECT * FROM sc_escalafon.tb_persona WHERE pers_dni = ?;";
        $sql = $conectar->prepare($sql);
        $sql->bindValue(1, $dni);
        $sql->execute();
        return $resultado = $sql->fetchAll();
    }
    /*  */
    public function get_usuarios()
    {
        $conectar = parent::conexion();
        parent::set_names();
        $sql = "SELECT tbper.pers_id, tbper.pers_nombre, tbper.pers_apelpat, tbper.pers_apelmat, tbper.pers_dni, tbp.perm_tipo
        from sc_escalafon.tb_persona tbper
        inner join sc_seguridad.tb_permiso tbp on tbper.pers_id = tbp.pers_id
		inner join sc_seguridad.tb_sistema ts on ts.sist_id = tbp.sist_id
        where ts.sist_iniciales = 'SIGI'";
        $sql = $conectar->prepare($sql);
        $sql->execute();
        return $resultado = $sql->fetchAll();
    }
    public function get_usuario_x_id($usua_id)
    {
        $conectar = parent::conexion();
        parent::set_names();
        $sql = "SELECT tbper.pers_id, tbper.pers_nombre, tbper.pers_apelpat, tbper.pers_apelmat, tbper.pers_dni, tbp.perm_tipo
        from sc_escalafon.tb_persona tbper
        inner join sc_seguridad.tb_permiso tbp on tbper.pers_id = tbp.pers_id
		inner join sc_seguridad.tb_sistema ts on ts.sist_id = tbp.sist_id
        where ts.sist_iniciales = 'SIGI' and tbper.pers_id = ?";
        $sql = $conectar->prepare($sql);
        $sql->bindValue(1, $usua_id);
        $sql->execute();
        return $resultado = $sql->fetchAll();
    }
    public function update_usuario_perfil($usu_id, $usu_nuevapass, $usu_pass)
    {
        $conectar = parent::conexion();
        parent::set_names();
        $sql = "UPDATE sc_giros.tm_usuario 
                    SET
                        usu_pass = ?
                    WHERE
                        usu_id = ? AND usu_pass = ?";
        $sql = $conectar->prepare($sql);
        $sql->bindValue(1, $usu_nuevapass);
        $sql->bindValue(2, $usu_id);
        $sql->bindValue(3, $usu_pass);
        $sql->execute();


        $filas_afectadas = $sql->rowCount();

        return $filas_afectadas > 0;
    }



    public function delete_usuario($usu_id)
    {
        $conectar = parent::conexion();
        parent::set_names();
        $sql = "UPDATE sc_giros.tm_usuario
                SET
                    est = 0
                WHERE
                    usu_id = ?";
        $sql = $conectar->prepare($sql);
        $sql->bindValue(1, $usu_id);
        $sql->execute();
        return $resultado = $sql->fetchAll();
    }
    public function get_usuario()
    {
        $conectar = parent::conexion();
        parent::set_names();
        $sql = "SELECT tbper.pers_id, tbper.pers_nombre, tbper.pers_apelpat, tbper.pers_apelmat, 
		tbper.pers_dni, tbp.perf_id
        from sc_escalafon.tb_persona tbper
        inner join sc_seguridad.tb_permiso tbp on tbper.pers_id = tbp.pers_id
		inner join sc_seguridad.tb_sistema ts on ts.sist_id = tbp.sist_id
        where ts.sist_iniciales = 'SIGI'";
        $sql = $conectar->prepare($sql);
        $sql->execute();
        return $resultado = $sql->fetchAll();
    }

    public function get_usuario_x_dni($usu_dni)
    {
        $conectar = parent::conexion();
        parent::set_names();
        $sql = "SELECT * FROM sc_giros.tm_usuario WHERE est=1 AND usu_dni=?";
        $sql = $conectar->prepare($sql);
        $sql->bindValue(1, $usu_dni);
        $sql->execute();
        return $resultado = $sql->fetchAll();
    }
    public function get_usu_modal($depe_id)
    {
        $conectar = parent::conexion();
        parent::set_names();
        $sql = "SELECT tbper.pers_id, tbper.pers_nombre, tbper.pers_apelpat, tbper.pers_apelmat, 
                tbper.pers_dni, tbper.pers_estado,  tbp.perf_id FROM sc_escalafon.tb_persona tbper
                    inner join sc_seguridad.tb_permiso tbp on tbper.pers_id = tbp.pers_id
                    inner join sc_seguridad.tb_sistema ts on ts.sist_id = tbp.sist_id
                    WHERE tbper.pers_estado = 'A' and ts.sist_iniciales = 'SIGI'
                    AND tbper.pers_id not in (select pers_id from sc_giros.td_areausu where depe_id=? and est = 1)";
        $sql = $conectar->prepare($sql);
        $sql->bindValue(1, $depe_id);
        $sql->execute();
        return $resultado = $sql->fetchAll();
    }
    public function get_area_usu_x_id($depe_id)
    {
        $conectar = parent::conexion();
        parent::set_names();
        $sql = "SELECT td.areausu_id, tbper.pers_id, tbper.pers_nombre, tbper.pers_apelpat, tbper.pers_apelmat, 
                tbper.pers_dni, tbper.pers_estado
                    from sc_escalafon.tb_persona  tbper inner join 
                    sc_giros.td_areausu td on tbper.pers_id = td.pers_id 
                    where td.depe_id = ?  and tbper.pers_estado = 'A' and td.est = 1";
        $sql = $conectar->prepare($sql);
        $sql->bindValue(1, $depe_id);
        $sql->execute();
        return $resultado = $sql->fetchAll();
    }
}
