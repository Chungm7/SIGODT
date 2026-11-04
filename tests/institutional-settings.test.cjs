const { test } = require('node:test');
const assert = require('node:assert/strict');
const fs = require('node:fs');
const path = require('node:path');
const root = path.join(__dirname, '..');
const view = fs.readFileSync(path.join(root, 'view/setting/index.php'), 'utf8');
const cssPath = path.join(root, 'public/css/institutional-settings.css');
const css = fs.existsSync(cssPath) ? fs.readFileSync(cssPath, 'utf8') : '';

test('settings uses literal heading and scoped stylesheet after shared head', () => {
  assert.match(view, /<h1[^>]*>CONFIGURACIÓN<\/h1>/);
  assert.ok(view.indexOf('institutional-settings.css') > view.indexOf('require_once("../html/mainHead.php")'));
  assert.match(view, /<main[^>]*id="institutional-settings"/);
  assert.doesNotMatch(view, /Ajustes de Cuenta|settings\.js|auth\/proxy|cambiar-clave|<form\b|<button\b/);
});
test('SIINDI sidebar and three supported information cards replace the monolithic panel', () => {
  assert.match(view, /<aside class="settings-sidebar"/);
  assert.match(view, /href="\.\.\/setting\/"[^>]*aria-current="page"/);
  assert.equal((view.match(/class="mpch-settings-card"/g) || []).length, 3);
  for (const title of ['Mi Perfil', 'Contacto y Cargo Institucional', 'Correo Electrónico']) assert.ok(view.includes(title));
  for (const id of ['nombre_completo', 'dni', 'rol', 'modulo', 'correo', 'celular']) {
    assert.match(view.replace(/<\?php[\s\S]*?\?>/g, ''), new RegExp(`<input[^>]*id="perfil_${id}"[^>]*readonly`));
    assert.match(view, new RegExp(`for="perfil_${id}"`));
  }
  assert.match(view, /href="\.\.\/inicio\/"/);
});
test('SIGODT session gate, retrieval and redirect stay unchanged', () => {
  assert.ok(view.startsWith(`<?php
require_once("../../config/conexion.php");

if (isset($_SESSION["usua_id_SIGODT"])) {
  $nombre_completo = $_SESSION["nombre_completo_SIGODT"] ?? "Usuario";
  $perfil = $_SESSION["rol_nombre_SIGODT"] ?? "Sin perfil";
  $dni = $_SESSION["usua_dni_SIGODT"] ?? "";
  $correo = trim($_SESSION["pers_emailm_SIGODT"] ?? "");
  $celular = trim($_SESSION["pers_celu01_SIGODT"] ?? "");
?>`));
  assert.ok(view.includes('header("Location:" . Conectar::ruta() . "view/404/");'));
  for (const include of ['mainHead', 'mainProfile', 'menu', 'footer', 'mainjs']) assert.ok(view.includes(`require_once("../html/${include}.php");`));
  assert.doesNotMatch(view, /\$_(?:POST|GET|COOKIE)|middleware|fetch\(|\$\.post|<input[^>]*(?:disabled|required|name=)/);
});
test('source-grounded SIINDI dimensions and mobile stacking remain isolated', () => {
  for (const contract of [/max-width: 1347px/, /padding: 20px 32px 52px/, /width: 224px/, /min-height: 580px/, /height: 52px/, /gap: 24px/, /padding: 28px/, /height: 42px/, /width: 48px/, /font-size: 13px/, /font-size: 15px/, /height: 5px/, /#0066CC 82%, #FBC02D 82%/]) assert.match(css, contract);
  assert.match(css, /@media \(max-width: 991px\)[\s\S]*flex-direction: column/);
  assert.match(css, /@media \(max-width: 768px\)[\s\S]*padding: 24px 16px 36px/);
  assert.match(css, /:focus-within/);
  assert.match(css, /prefers-reduced-motion/);
  // Every rule is scoped, including rules inside media queries.
  const selectors = [...css.replace(/\/\*[\s\S]*?\*\//g, '').matchAll(/([^{}]+)\{/g)].map(m => m[1].trim()).filter(s => !s.startsWith('@'));
  assert.ok(selectors.length > 15);
  for (const selector of selectors) assert.ok(selector.split(',').every(s => s.trim().startsWith('#institutional-settings')), selector);
});
