<?php
/**
 * Vista de Recuperación de Acceso con Código de Respaldo
 * Permite al funcionario ingresar cuando perdió o no tiene su celular.
 * 
 * Municipalidad Provincial de Chiclayo (MPCH)
 */

if (file_exists(__DIR__ . '/../../../config/conexion.php')) {
    require_once __DIR__ . '/../../../config/conexion.php';
    require_once __DIR__ . '/../../../config/jwt.php';
} else {
    require_once __DIR__ . '/../../config/conexion.php';
    require_once __DIR__ . '/../../config/jwt.php';
}

$jwt = new JwtHandler();
$pendingData = null;

if (!empty($_COOKIE['2fa_pending'])) {
    $dec = $jwt->decrypt($_COOKIE['2fa_pending']);
    if (!empty($dec->data)) {
        $pendingData = $dec->data;
    }
}

if (!$pendingData || empty($pendingData->usu_doc)) {
    header("Location: " . Conectar::ruta());
    exit();
}

$dniEnmascarado = substr($pendingData->usu_doc, 0, 2) . '****' . substr($pendingData->usu_doc, -2);
$headerSisAbrNom = mb_strtoupper($_ENV['SIST_INIC'], 'UTF-8');
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover" />
    <meta http-equiv="X-UA-Compatible" content="ie=edge" />
    <title><?= htmlspecialchars($headerSisAbrNom) ?></title>
    <link href="../../public/css/tabler.min.css?1692870487" rel="stylesheet"/>
    <link href="../assets/css/two_factor.css" rel="stylesheet"/>
    <link rel="icon" type="image/png" href="../../public/static/illustrations/logomuni3.png">
    <style>
        body {
            position: relative;
            margin: 0;
            min-height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
            background-color: #f8f9fa;
            overflow-x: hidden;
        }
        body::before {
            content: '';
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-image: url('../../public/static/illustrations/MPCH.png');
            background-size: cover;
            background-position: center center;
            background-repeat: no-repeat;
            filter: brightness(50%);
            z-index: -1;
        }
        .card {
            position: relative;
            z-index: 1;
            background: white;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
            border-radius: 10px;
            width: 91%;
        }
    </style>
</head>
<body class="d-flex flex-column">
    <div class="page page-center">
        <div class="container container-tight py-2">
            
            <!-- Franja Institucional de 4 Colores MPCH -->
            <div class="header-colors" style="width: 91%; height: 10px; display: flex; margin: 0 auto;">
                <div style="background: #f32930ff; width: 25%; height: 100%; border-radius: 5px 0 0 0;"></div>
                <div style="background: #fcd75eff; width: 25%; height: 100%;"></div>
                <div style="background: #4ec590ff; width: 25%; height: 100%;"></div>
                <div style="background: #2484bbff; width: 25%; height: 100%; border-radius: 0 5px 0 0;"></div>
            </div>

            <!-- Tarjeta de Recuperación -->
            <div class="card card-md mx-auto" style="border-radius: 0 0 5px 5px; width: 91%;">
                <div class="card-body p-4">
                    
                    <div class="text-center mb-3">
                        <img src="../../public/static/illustrations/logomuni3.png" width="80" height="80" draggable="false" alt="Escudo MPCH">
                    </div>

                    <div class="text-center mb-3">
                        <div class="icon-2fa-shield mb-2" style="background:#fef3c7; color:#d97706;">
                            <svg xmlns="http://www.w3.org/2000/svg" class="icon icon-tabler icon-tabler-key" width="32" height="32" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round">
                                <path stroke="none" d="M0 0h24v24H0z" fill="none"/>
                                <path d="M16.555 3.843l3.602 3.602a2.877 2.877 0 0 1 0 4.069l-2.643 2.643a2.877 2.877 0 0 1 -4.069 0l-.301 -.301l-6.558 6.558a2 2 0 0 1 -1.239 .578l-.175 .008h-1.172a1 1 0 0 1 -.993 -.883l-.007 -.117v-1.172a2 2 0 0 1 .467 -1.284l.119 -.13l.414 -.414h2v-2h2v-2l2.144 -2.144l-.301 -.301a2.877 2.877 0 0 1 0 -4.069l2.643 -2.643a2.877 2.877 0 0 1 4.069 0z" />
                                <path d="M15 9h.01" />
                            </svg>
                        </div>
                        <h2 class="h3 fw-bold text-dark mb-1">Recuperación de Acceso</h2>
                        <p class="text-muted small mb-0">
                            Ingresa uno de tus <strong>Códigos de Respaldo</strong> de un solo uso guardados al activar tu cuenta (DNI: <strong><?= htmlspecialchars($dniEnmascarado) ?></strong>).
                        </p>
                    </div>

                    <div id="alert2FA" class="alert d-none mb-3" role="alert"></div>

                    <form id="form2FARecovery" autocomplete="off" novalidate>
                        <div class="mb-3">
                            <label class="form-label text-center d-block fw-bold text-muted small text-uppercase">
                                Código de Respaldo (Formato: XXXX-XXXX)
                            </label>
                            <input 
                                type="text" 
                                id="codigo_respaldo" 
                                name="codigo_respaldo" 
                                class="form-control text-center font-monospace fw-bold fs-2" 
                                placeholder="8492-3105" 
                                maxlength="9" 
                                style="letter-spacing: 3px; height: 50px;"
                                required
                            />
                            <div class="form-text text-center text-muted small mt-2">
                                Nota: Cada código de respaldo queda automáticamente invalidado tras su uso exitoso.
                            </div>
                        </div>

                        <div class="form-footer mt-4">
                            <button type="submit" id="btnSubmitRecovery" class="btn btn-warning w-100 py-2 fw-bold text-dark">
                                Validar Código de Respaldo
                            </button>
                        </div>
                    </form>

                    <div class="text-center mt-3 pt-3 border-top">
                        <a href="desafio_login.php" class="text-muted small text-decoration-none">
                            &larr; Volver a intentar con Google Authenticator
                        </a>
                    </div>

                </div>
            </div>

        </div>
    </div>

    <script src="../../public/js/jquery.min.js"></script>
    <script src="../../public/js/tabler.min.js" defer></script>
    <script src="../assets/js/two_factor.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            TwoFactorApp.initRecovery();
        });
    </script>
</body>
</html>
