# reCAPTCHA v3 en el login

reCAPTCHA v3 es una capa **adicional** al CAPTCHA de imagen (`docs/login-captcha.md`). Ambas validaciones deben pasar. Se ejecutan en `Usuario::login` **antes de conectar a la base de datos o llamar a sisSeguridad**: primero la imagen y luego reCAPTCHA. Un fallo de reCAPTCHA redirige a `index.php?m=12`.

## Configuración

Defina las variables en `.env` (ver `.env.example`). Las claves reales viven sólo en `.env`, que Git ignora.

| Variable | Descripción |
|---|---|
| `RECAPTCHA_ENABLED` | `true` para activar. Por defecto desactivado. |
| `RECAPTCHA_SITE_KEY` | Clave pública. Es la única que llega al navegador (`data-recaptcha-site-key`). |
| `RECAPTCHA_SECRET_KEY` | Clave secreta. Sólo la lee el servidor. |
| `RECAPTCHA_MIN_SCORE` | Puntaje mínimo aceptado, de 0 a 1. Por defecto `0.5`. |
| `RECAPTCHA_EXPECTED_HOSTNAME` | Opcional. Si existe, Google debe reportar ese dominio. |

Estados: la función se activa sólo si el indicador es verdadero **y** existen ambas claves. Con el indicador apagado se omite únicamente la comprobación de reCAPTCHA. Con el indicador activo y una clave ausente el login se rechaza (falla cerrada) para evitar una omisión silenciosa.

## Contrato

- El servidor consulta `https://www.google.com/recaptcha/api/siteverify` con timeout corto y verificación TLS.
- Exige `success === true`, `action === 'login'`, el dominio esperado si está configurado y `score >= RECAPTCHA_MIN_SCORE`.
- Token ausente, malformado o demasiado largo, error de red, respuesta no JSON, acción distinta, puntaje bajo o dominio distinto rechazan el acceso.
- Se envía `remoteip` sólo cuando `REMOTE_ADDR` es una IP válida; no se usan cabeceras reenviadas.
- En el navegador, `public/js/login-recaptcha.js` carga el script v3 con la clave pública, solicita un token con la acción `login` al enviar y completa `recaptchaToken`. Si el script o el token fallan, muestra un error en español, no envía el formulario y renueva el CAPTCHA de imagen.

## Verificación sin servicios externos

```sh
php tests/login_recaptcha_test.php
node --test tests/login-recaptcha.test.cjs tests/login-captcha.test.cjs
```

Las pruebas usan claves falsas y un cliente HTTP simulado. La verificación real con Google y el comportamiento en navegador requieren claves reales y un dominio desplegado; no están cubiertos aquí.
