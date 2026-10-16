const test = require('node:test');
const assert = require('node:assert/strict');
const fs = require('node:fs');
const vm = require('node:vm');

const usuJsPath = 'view/giros_ciudadano/usudetalleciudadano.js';
const usuModalPhp = 'view/giros_ciudadano/modalmantenimiento.php';
const usuIndexPhp = 'view/giros_ciudadano/index.php';

test('modalmantenimiento.php has search button for RUC and readonly razon social', () => {
  const php = fs.readFileSync(usuModalPhp, 'utf8');
  assert.match(php, /id="btn_buscar_doc"/);
  assert.match(php, /id="btn_buscar_ruc"/);
  assert.match(php, /btn-buscar-doc/);
  assert.match(php, /onclick="ejecutarBusquedaRuc\(\)"/);
  assert.match(php, /id="empr_razon_social"[^>]*readonly/);
});

test('index.php does not search automatically on completing DNI or RUC', () => {
  const indexSource = fs.readFileSync(usuIndexPhp, 'utf8');
  // Check limitabuscadni does NOT call buscarDNI on max_length
  const limitabuscadniMatch = indexSource.match(/function limitabuscadni\([\s\S]*?\n\s{6}\}/);
  assert.ok(limitabuscadniMatch, 'limitabuscadni defined');
  assert.doesNotMatch(limitabuscadniMatch[0], /buscarDNI\(/, 'limitabuscadni must not call buscarDNI automatically');

  // Check limitarbuscarruc does NOT call buscaRUC on length 11
  const limitarrucMatch = indexSource.match(/function limitarbuscarruc\([\s\S]*?\n\s{6}\}/);
  assert.ok(limitarrucMatch, 'limitarbuscarruc defined');
  assert.doesNotMatch(limitarrucMatch[0], /buscaRUC\(/, 'limitarbuscarruc must not call buscaRUC automatically');
});

test('usudetalleciudadano.js has limpiarFormularioMantenimiento and cleans on hidden.bs.modal', () => {
  const source = fs.readFileSync(usuJsPath, 'utf8');
  assert.match(source, /function limpiarFormularioMantenimiento\(\)/);
  assert.match(source, /\$\('#modalmantenimiento'\)\.on\('hidden\.bs\.modal',\s*function\s*\(\)\s*\{[\s\S]*?limpiarFormularioMantenimiento\(\)/);
  assert.match(source, /function ejecutarBusquedaRuc\(\)/);
  assert.match(source, /function buscaRUC\(\)/);
});
