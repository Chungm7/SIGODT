const { test } = require('node:test');
const assert = require('node:assert/strict');
const fs = require('node:fs');
const vm = require('node:vm');
const { spawnSync } = require('node:child_process');
const pdf = require('../view/reporte_usuario/reportepdf.js');
function browser() {
  const ctx = { console, navigator: { userAgent: 'synthetic' }, document: { createElement: () => ({}) }, atob, btoa, TextEncoder, TextDecoder, Uint8Array, ArrayBuffer, Blob, setTimeout };
  ctx.window = ctx; ctx.self = ctx;
  vm.createContext(ctx);
  for (const file of ['jspdf/jspdf.umd.min.js', 'jspdf-autotable/jspdf.plugin.autotable.min.js']) vm.runInContext(fs.readFileSync(`public/lib/${file}`, 'utf8'), ctx);
  return ctx;
}
function fixture(count) {
  return { emitter: 'José Muñoz', generated_at: '2025-01-31 12:00:00', summary: { orders: count, cancelled: Math.min(2, count), lines: count, amount: (count * 123.45).toFixed(2) },
    details: Array.from({ length: count }, (_, i) => ({ order_id: `ORDEN-${i}-FIN`, emitted_at: '2025-01-31 23:59:59', citizens: ['María Peña ' + 'Nombre extenso '.repeat(i === 0 ? 220 : 3) + 'CIUDADANO-FIN'], documents: ['Resolución'], codes: ['CÓDIGO-Ñ-ENTERO'], procedures: ['Autorización y licencia municipal'], state_label: 'Anulado', amount: '123.45' })),
    breakdown: count ? [{ document: 'Resolución', code: 'Ñ-1', procedure: 'Autorización', orders: count, lines: count, amount: (count * 123.45).toFixed(2) }] : [] };
}
const filters = { start: '2025-01-01', end: '2025-01-31', document: '3', procedure: '4' };
const font = () => fs.readFileSync('public/lib/report-pdf-fonts/PT_Sans-Web-Regular.ttf').toString('base64');
test('S10: actual browser distributions create selectable multipage landscape PDF with complete data', { timeout: 30000 }, () => {
  const doc = pdf.build(fixture(65), filters, font(), browser());
  const bytes = Buffer.from(doc.output('arraybuffer'));
  assert.equal(bytes.subarray(0, 5).toString(), '%PDF-');
  assert.ok(doc.getNumberOfPages() > 2);
  assert.ok(doc.internal.pageSize.getWidth() > doc.internal.pageSize.getHeight());
  const extracted = spawnSync('pdftotext', ['-layout', '-', '-'], { input: bytes, timeout: 10000 });
  if (extracted.error?.code === 'ENOENT') return;
  assert.equal(extracted.status, 0, extracted.stderr.toString());
  const text = extracted.stdout.toString();
  for (const expected of ['SIGODT', 'José Muñoz', 'María Peña', 'Resolución', 'Autorización', 'CIUDADANO-FIN', 'ORDEN-64-FIN', 'CÓDIGO-Ñ-ENTERO', '123.45', 'no equivale', 'no son aditivos', 'Documento (ID): 3', 'Procedimiento (ID): 4']) assert.ok(text.includes(expected), expected);
  for (const heading of ['N.º de orden', 'Fecha y hora', 'Ciudadano /', 'Documento', 'Código del', 'Nombre del', 'Estado actual', 'Importe']) assert.ok(text.includes(heading), heading);
  const pages = text.split('\f').filter(s => s.trim());
  assert.equal(pages.length, doc.getNumberOfPages());
  pages.forEach((page, i) => { assert.ok(page.includes('SIGODT')); assert.ok(page.includes(`Página ${i + 1} de ${pages.length}`)); });
  assert.ok((text.match(/N.º de orden/g) || []).length > 1);
  assert.equal((text.match(/CIUDADANO-FIN/g) || []).length, 65);
  assert.equal((text.replace(/\s+/g, ' ').match(/Nombre extenso/g) || []).length, 220 + 64 * 3);
});
test('S10: empty report, missing library, safe deterministic filenames', () => {
  const doc = pdf.build(fixture(0), filters, font(), browser());
  assert.equal(doc.getNumberOfPages(), 1);
  const extracted = spawnSync('pdftotext', ['-', '-'], { input: Buffer.from(doc.output('arraybuffer')), timeout: 10000 });
  if (!extracted.error) {
    assert.equal(extracted.status, 0);
    assert.match(extracted.stdout.toString(), /No se encontraron órdenes/);
    assert.match(extracted.stdout.toString(), /Sin conceptos coincidentes/);
  }
  assert.throws(() => pdf.build(fixture(0), filters, font(), {}), /PDF/);
  assert.equal(pdf.filename(filters), 'emisiones-2025-01-01-2025-01-31.pdf');
  assert.throws(() => pdf.filename({ start: '../José', end: 'x' }));
});
test('S10: pinned provenance hashes, local view-only load order and no remote rendering path', () => {
  const crypto = require('node:crypto');
  for (const entry of JSON.parse(fs.readFileSync('public/lib/report-pdf-fonts/provenance.json'))) assert.equal(crypto.createHash('sha256').update(fs.readFileSync(entry.file)).digest('hex'), entry.sha256);
  const view = fs.readFileSync('view/reporte_usuario/index.php', 'utf8');
  const scripts = ['../../public/lib/jspdf/jspdf.umd.min.js', '../../public/lib/jspdf-autotable/jspdf.plugin.autotable.min.js', 'reportepdf.js', 'reporteusuario.js'];
  let last = -1;
  for (const script of scripts) { const pos = view.indexOf(`src="${script}"`); assert.ok(pos > last, script); last = pos; }
  const source = fs.readFileSync('view/reporte_usuario/reportepdf.js', 'utf8');
  assert.doesNotMatch(source, /https?:|html2canvas|\.html\(|addImage|loadImage|window\.open/);
});
test('S10: local font loading uses same session and exposes failures for user retry', async t => {
  const previous = globalThis.fetch;
  t.after(() => { globalThis.fetch = previous; });
  globalThis.fetch = async (url, options) => {
    assert.equal(url, '../../public/lib/report-pdf-fonts/PT_Sans-Web-Regular.ttf');
    assert.deepEqual(options, { credentials: 'same-origin', cache: 'no-store' });
    const bytes = fs.readFileSync('public/lib/report-pdf-fonts/PT_Sans-Web-Regular.ttf');
    return { ok: true, arrayBuffer: async () => bytes.buffer.slice(bytes.byteOffset, bytes.byteOffset + bytes.byteLength) };
  };
  assert.equal(await pdf.loadFont(), font());
  globalThis.fetch = async () => ({ ok: false });
  await assert.rejects(pdf.loadFont(), /fuente PDF/);
});
module.exports = { browser, fixture, filters, font };
