<?php
require_once __DIR__ . '/../helpers/Captcha.php';
require_once __DIR__ . '/../helpers/Recaptcha.php';
class usuario extends conectar
{
    public function login()
    {
        if (!Captcha::consume($_SESSION, $_POST['captchaInput'] ?? null)) {
            header("Location:" . Conectar::ruta() . "index.php?m=11");
            exit();
        }
        // reCAPTCHA v3 es una capa adicional: ambas validaciones deben pasar antes de la BD y sisSeguridad.
        $remoteIp = $_SERVER['REMOTE_ADDR'] ?? null;
        if (!Recaptcha::check($_POST['recaptchaToken'] ?? null, $remoteIp)) {
            header("Location:" . Conectar::ruta() . "index.php?m=12");
            exit();
        }
        $dni = $_POST['usu_dni'] ?? null;
        $pass = $_POST['usu_pass'] ?? null;
        if (!is_string($dni) || !is_string($pass) || empty($dni) || empty($pass)) {
            header("Location:" . Conectar::ruta() . "index.php?m=2");
            exit();
        }

        $conectar = parent::conexion();
        parent::set_names();

        if (isset($_POST["enviar"])) {
            $ip = Conectar::getClientIp();

            // 1. Acquire system Bearer token from SIGUR auth endpoint
            $sigurUrl = rtrim(Conectar::getEnv('SIGUR_URL', 'http://10.10.10.16/sigur/'), '/') . '/';
            $authUrl = Conectar::getEnv('SIGUR_AUTH_URL', '');
            if (empty($authUrl)) {
                if (preg_match('#/ws/?$#', $sigurUrl)) {
                    $authUrl = preg_replace('#/ws/?$#', '/auth/', $sigurUrl);
                } else {
                    $authUrl = $sigurUrl . 'auth/';
                }
            }
            $sistInic = Conectar::getEnv('SIST_INIC', Conectar::getEnv('SIS_SEGURIDAD_INIT', 'SIGI'));
            $sistPass = Conectar::getEnv('SIST_PASS', 'sgd*2025**');

            $chAuth = curl_init();
            curl_setopt($chAuth, CURLOPT_URL, $authUrl);
            curl_setopt($chAuth, CURLOPT_POST, true);
            curl_setopt($chAuth, CURLOPT_POSTFIELDS, http_build_query([
                'sist_inic' => $sistInic,
                'sist_pass' => $sistPass,
            ]));
            curl_setopt($chAuth, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($chAuth, CURLOPT_TIMEOUT, 15);
            $authResponse = curl_exec($chAuth);

            if (curl_errno($chAuth)) {
                $curlError = curl_error($chAuth);
                curl_close($chAuth);
                $_SESSION['login_error'] = 'Error al conectarse al servicio de autenticación';
                header("Location: " . Conectar::ruta() . "index.php");
                exit();
            }
            curl_close($chAuth);

            $authData = json_decode($authResponse, true);
            $token = $authData['data']['token'] ?? null;
            if (empty($token) || !is_string($token)) {
                $message = $authData['message'] ?? 'Error al obtener token de autenticación';
                $_SESSION['login_error'] = $message;
                header("Location: " . Conectar::ruta() . "index.php");
                exit();
            }

            // 2. Call SIGUR login endpoint
            $loginUrl = Conectar::getEnv('SIGUR_LOGIN_URL', '');
            if (empty($loginUrl)) {
                $loginUrl = rtrim($sigurUrl, '/') . '/ws/login.php';
            }

            $chLogin = curl_init();
            curl_setopt($chLogin, CURLOPT_URL, $loginUrl);
            curl_setopt($chLogin, CURLOPT_POST, true);
            curl_setopt($chLogin, CURLOPT_POSTFIELDS, http_build_query([
                'usu_doc' => $dni,
                'usu_pass' => $pass,
                'usu_ip' => $ip,
                'usu_tipo' => 'P',
                'sist_inic' => $sistInic,
            ]));
            curl_setopt($chLogin, CURLOPT_HTTPHEADER, [
                'Authorization: Bearer ' . $token,
            ]);
            curl_setopt($chLogin, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($chLogin, CURLOPT_TIMEOUT, 15);
            $loginResponse = curl_exec($chLogin);

            if (curl_errno($chLogin)) {
                $curlError = curl_error($chLogin);
                curl_close($chLogin);
                $_SESSION['login_error'] = 'Error al conectarse al servicio: ' . $curlError;
                header("Location: " . Conectar::ruta() . "index.php");
                exit();
            }
            curl_close($chLogin);

            $res = json_decode($loginResponse, true);
            if (!is_array($res)) {
                $_SESSION['login_error'] = 'Respuesta inválida del servicio';
                header("Location: " . Conectar::ruta() . "index.php");
                exit();
            }

            // 3. Check for password whitening (blanqueamiento)
            if (!empty($res['data']['url']) || ($res['detalle'] ?? '') === 'Blanqueamiento de clave') {
                require_once __DIR__ . '/../config/jwt.php';
                $jwt = new JwtHandler();
                $param = [
                    "usu_doc" => $dni,
                    "usu_tipo" => "P",
                    "success" => true,
                    "blanqueamiento" => 2
                ];
                $jweToken = $jwt->encrypt($param, 'blanqueamiento', 1900);
                $jwt->setCookie("blanqueamiento", $jweToken, 1900, "/sigur/");
                header("Location: " . $sigurUrl . "view/clave/blanqueamiento.php");
                exit();
            }

            // 4. Handle login failure
            $status = (int)($res['status'] ?? 0);
            $success = !empty($res['success']);
            if ($status !== 200 || !$success) {
                $message = $res['message'] ?? $res['detalle'] ?? 'Datos incorrectos';
                if (!is_string($message) || trim($message) === '') {
                    $message = 'Datos incorrectos';
                }
                $_SESSION['login_error'] = $message;
                header("Location: " . Conectar::ruta() . "index.php");
                exit();
            }

            // 5. Successful login: extract user id and query local db
            $userDataRes = $res['data'] ?? [];
            $persId = $userDataRes['usu_id'] ?? $userDataRes['pers_id'] ?? null;
            if (empty($persId)) {
                $_SESSION['login_error'] = 'Identificador de usuario no válido';
                header("Location: " . Conectar::ruta() . "index.php");
                exit();
            }

            $sql = "SELECT p.pers_id, p.pers_dni, p.pers_nombre, p.pers_apelpat, p.pers_apelmat,
                           p.pers_estado, p.pers_emailm, p.pers_celu01, p.pers_foto,
                           perm.perf_id, pf.perf_nombre, perm.depe_id
                    FROM sc_escalafon.tb_persona p
                    INNER JOIN sc_seguridad.tb_permiso perm ON p.pers_id = perm.pers_id 
                        AND perm.sist_id = 8 
                        AND perm.perm_estado = 'A'
                    INNER JOIN sc_seguridad.tb_perfil pf ON perm.perf_id = pf.perf_id
                    WHERE p.pers_id = :pers_id;";
            $stmt = $conectar->prepare($sql);
            $stmt->bindValue(':pers_id', $persId, PDO::PARAM_INT);
            $stmt->execute();
            $userData = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$userData) {
                $_SESSION['login_error'] = 'El usuario no tiene permisos activos para el sistema SIGODT';
                header("Location: " . Conectar::ruta() . "index.php");
                exit();
            }

            // 6. Record session history in sc_seguridad.tb_historial_sesion
            $hise_id = null;
            try {
                $histSql = "INSERT INTO sc_seguridad.tb_historial_sesion (
                                pers_id, hise_ip, hise_fechafin, hise_horafin
                            ) VALUES (
                                :pers_id, :hise_ip, (CURRENT_TIMESTAMP + interval '5 hours')::date, (CURRENT_TIMESTAMP + interval '5 hours')::time
                            ) RETURNING hise_id;";
                $histStmt = $conectar->prepare($histSql);
                $histStmt->bindValue(':pers_id', $userData['pers_id'], PDO::PARAM_INT);
                $histStmt->bindValue(':hise_ip', $ip, PDO::PARAM_STR);
                $histStmt->execute();
                $histRow = $histStmt->fetch(PDO::FETCH_ASSOC);
                if ($histRow && !empty($histRow['hise_id'])) {
                    $hise_id = $histRow['hise_id'];
                }
            } catch (Exception $e) {
                error_log("Failed to insert session history: " . $e->getMessage());
            }

            // 7. Auto-provision default area via Area::sincronizar_area_defecto
            if (!empty($userData['pers_id']) && !empty($userData['depe_id'])) {
                require_once __DIR__ . '/Area.php';
                $areaModel = new Area();
                $areaModel->sincronizar_area_defecto($userData['pers_id'], $userData['depe_id']);
            }

            // 8. Populate session variables
            $_SESSION["usua_id_SIGODT"] = $userData["pers_id"];
            $_SESSION["usua_dni_SIGODT"] = $userData["pers_dni"];
            $_SESSION["rol_id_SIGODT"] = $userData["perf_id"];
            $_SESSION["usua_estado_SIGODT"] = $userData["pers_estado"];
            $_SESSION["pers_apellidos_SIGODT"] = trim(($userData["pers_apelpat"] ?? '') . " " . ($userData["pers_apelmat"] ?? ''));
            $_SESSION["pers_nombre_SIGODT"] = $userData["pers_nombre"] ?? '';
            $_SESSION["nombre_completo_SIGODT"] = trim(($userData["pers_nombre"] ?? '') . " " . ($userData["pers_apelpat"] ?? '') . " " . ($userData["pers_apelmat"] ?? ''));
            $_SESSION["rol_nombre_SIGODT"] = $userData["perf_nombre"] ?? '';
            $_SESSION["pers_emailm_SIGODT"] = $userData["pers_emailm"] ?? '';
            $_SESSION["pers_celu01_SIGODT"] = $userData["pers_celu01"] ?? '';
            $_SESSION["historial_login_SIGODT"] = $hise_id;
            $_SESSION["usu_depe_id_SIGODT"] = $userData["depe_id"];

            $rawFoto = !empty($userData["pers_foto"]) ? trim($userData["pers_foto"]) : '';
            if ($rawFoto !== '') {
                $_SESSION["usua_foto_SIGODT"] = (strpos($rawFoto, 'data:image') === 0)
                    ? $rawFoto
                    : 'data:image/jpeg;base64,' . $rawFoto;
            } else {
                $_SESSION["usua_foto_SIGODT"] = '';
            }

            // 9. Redirect to main module (Inicio)
            header("Location: " . Conectar::ruta() . "view/inicio/");
            exit();
        }
    }
    public function logout($hise_id)
    {
        $conectar = parent::conexion();
        parent::set_names();
        $hise_id = $hise_id;

        //comienzo API seguridad
        $ch = curl_init();
        $sisSeguridadUrl = rtrim(Conectar::getEnv('SIS_SEGURIDAD_URL', 'http://10.10.10.16/sisSeguridad/'), '/') . '/';
        $ws_reniec = $sisSeguridadUrl . "ws/ws.php/?op=logout&hise_id=" . urlencode((string)$hise_id);

        curl_setopt($ch, CURLOPT_URL, $ws_reniec);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 2);
        curl_setopt($ch, CURLOPT_TIMEOUT, 3);
        $response = curl_exec($ch);

        if (curl_errno($ch)) {
            $error_msg = curl_error($ch);
            echo json_encode(array('error' => 'Error al conectarse al servicio'));
        } else {
            curl_close($ch);
        }

        $_SESSION = [];
        if (ini_get("session.use_cookies")) {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', [
                'expires'  => time() - 42000,
                'path'     => $params["path"],
                'domain'   => $params["domain"],
                'secure'   => $params["secure"],
                'httponly' => $params["httponly"],
                'samesite' => $params["samesite"] ?? 'Lax'
            ]);
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
        $sisSeguridadUrl = rtrim(Conectar::getEnv('SIS_SEGURIDAD_URL', 'http://10.10.10.16/sisSeguridad/'), '/') . '/';
        $ws_reniec = $sisSeguridadUrl . "ws/ws.php/?op=cambiar_contrasena&pers_id=" . urlencode((string)$pers_id) . "&claveantigua=" . urlencode((string)$claveantigua) . "&clave=" . urlencode((string)$clave) . "&clave2=" . urlencode((string)$clave2);

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

    public function get_foto_persona($pers_id)
    {
        try {
            $conectar = parent::conexion();
            parent::set_names();
            $sql = "SELECT pers_foto FROM sc_escalafon.tb_persona WHERE pers_id = ? LIMIT 1;";
            $stmt = $conectar->prepare($sql);
            $stmt->bindValue(1, (int)$pers_id, PDO::PARAM_INT);
            $stmt->execute();
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            $raw = !empty($row["pers_foto"]) ? trim($row["pers_foto"]) : '';
            if ($raw !== '') {
                return (strpos($raw, 'data:image') === 0)
                    ? $raw
                    : 'data:image/jpeg;base64,' . $raw;
            }
        } catch (Exception $e) {
            // Silencioso ante fallos para asegurar el fallback a perfil.jpeg
        }
        return '';
    }
}
