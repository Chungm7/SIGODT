# CAPTCHA obligatorio antes de autenticar

El login rechaza desafíos ausentes, incorrectos, malformados, vencidos o reutilizados **antes de conectar a la base de datos o llamar a sisSeguridad**. Un CAPTCHA correcto sólo permite continuar con la autenticación existente: nunca crea una sesión autenticada por sí mismo.

## Uso y requisito de despliegue

1. Habilite la extensión **PHP GD** en el entorno web antes de desplegar. Sin GD, el servicio responde HTTP 503 y el login permanece bloqueado; no existe alternativa que omita el CAPTCHA.
2. Espere a que cargue la imagen e ingrese sus seis caracteres junto con sus credenciales.
3. Si hay un error o el código vence, pulse actualizar. Después de cualquier envío debe resolver un nuevo desafío.

| Contrato | Comportamiento |
|---|---|
| Generación | `random_int`, seis caracteres sin símbolos ambiguos. La respuesta JSON exitosa contiene sólo `captcha` (imagen PNG), nunca el texto. |
| Vigencia | Cinco minutos desde la generación; a los 300 segundos exactos ya está vencido. |
| Consumo | Cada llamada a `Usuario::login` consume el desafío antes de validar el código o las credenciales, incluso si están malformados. Se aceptan letras minúsculas; no se recortan espacios. |
| Actualización | Invalida primero el desafío anterior. La nueva respuesta se registra en sesión sólo si el renderizado termina correctamente. |
| Fallos | JSON con HTTP 503 y sin desafío vigente si falta GD o falla el renderizado; HTTP 400 para una acción inválida. Respuestas `no-store`. |
| Navegador | Validación requerida, botón bloqueado hasta decodificar la imagen, envíos duplicados bloqueados y peticiones de actualización serializadas. Timeout de 15 segundos, error visible y reintento manual. |

## Verificación sin servicios externos

Desde la raíz del proyecto:

```sh
php tests/login_captcha_test.php
node --test tests/login-captcha.test.cjs
php -l index.php
php -l captcha.php
php -l models/Usuario.php
php -l helpers/Captcha.php
php -l tests/login_captcha_test.php
git diff --check
```

- PHP prueba el método real `Usuario::login` con una clase `Conectar` sustituta que interrumpe la ejecución en la frontera de credenciales. Ningún caso contacta DB, cURL o sisSeguridad ni carga configuración productiva.
- El servidor HTTP local de pruebas usa sesiones en memoria y verifica encabezados, JSON, estados HTTP e invalidación del desafío anterior. No escribe archivos de sesión.
- Las pruebas de lógica de generación usan un renderer sustituto **sin imagen real** para verificar publicación, actualización y fallos. La prueba PNG/HTTP exitosa sólo se ejecuta si GD está disponible.
- Node usa elementos DOM sustitutos y promesas controladas para probar carga, errores, timeout, refresco, callbacks obsoletos y envío duplicado; no reemplaza una inspección visual en navegador.

## Límites y revisión

En el entorno verificado falta GD: se observó el fallo cerrado HTTP 503, pero **no se verificó generación PNG real exitosa**. Tampoco se probó un login real contra sisSeguridad: requiere un entorno y credenciales autorizados. No cambian el contrato externo de autenticación ni los permisos existentes.

Revise primero `models/Usuario.php` (orden de validación), luego `helpers/Captcha.php`/`captcha.php` (ciclo de vida) y finalmente `index.php`/`public/js/login-captcha.js` (feedback). El desafío se comparte por sesión: actualizar en otra pestaña invalida la imagen anterior. Este control no sustituye límites de intentos, protección CSRF ni otros controles de autenticación.
