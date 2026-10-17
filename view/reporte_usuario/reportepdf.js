/* Vector-only export: all data comes from the authenticated full-report endpoint. */
(function (root) {
  'use strict';
  const headings = ['N.º de orden', 'Fecha y hora de emisión', 'Ciudadano / Razón social', 'Documento', 'Código del procedimiento', 'Nombre del procedimiento', 'Estado actual', 'Importe (S/)'];
  const text = value => Array.isArray(value) ? value.join(' / ') : String(value ?? '');
  function filename(filters) {
    if (![filters.start, filters.end].every(value => /^\d{4}-\d{2}-\d{2}$/.test(value))) throw new Error('Periodo PDF inválido.');
    return `emisiones-${filters.start}-${filters.end}.pdf`;
  }
  function build(data, filters, font, env = root) {
    if (!env.jspdf?.jsPDF || !font) throw new Error('No se pudo cargar PDF. Recargue la página e intente nuevamente.');
    const doc = new env.jspdf.jsPDF({ orientation: 'landscape', unit: 'mm', format: 'a4' });
    if (typeof doc.autoTable !== 'function') throw new Error('No se pudo cargar la tabla PDF. Recargue la página.');
    doc.addFileToVFS('PTSans.ttf', font);
    doc.addFont('PTSans.ttf', 'PTSans', 'normal');
    doc.setFont('PTSans', 'normal');
    doc.setProperties({ title: 'Reporte', creator: 'SIGODT' });
    const common = {
      margin: { top: 20, bottom: 16, left: 12, right: 12 },
      styles: { font: 'PTSans', fontStyle: 'normal', fontSize: 8, cellPadding: 2, overflow: 'linebreak', valign: 'top' },
      headStyles: { fontStyle: 'normal', fillColor: [32, 107, 196] },
      rowPageBreak: 'avoid', showHead: 'everyPage'
    };
    const table = options => {
      doc.autoTable({ ...common, startY: doc.lastAutoTable ? doc.lastAutoTable.finalY + 5 : 21, ...options });
    };
    table({ theme: 'plain', styles: { ...common.styles, fontSize: 11 }, body: [
      ['Reporte'],
      [`Emisor: ${text(data.emitter)}`],
      [`Periodo inclusivo: ${filters.start} al ${filters.end} · Generado: ${text(data.generated_at)}`],
      [`Documento (ID): ${filters.document || 'Todos'} · Procedimiento (ID): ${filters.procedure || 'Todos'}`]
    ] });
    table({ head: [['Total de órdenes emitidas', 'Órdenes anuladas y extornadas', 'Total de tasas emitidas', 'Importe emitido (S/)']],
      body: [[data.summary.orders, data.summary.cancelled, data.summary.lines, data.summary.amount]], theme: 'grid',
      columnStyles: { 3: { halign: 'right' } } });
    table({ theme: 'plain', body: [['El importe emitido no equivale a dinero recaudado. Solo conceptos coincidentes. Se incluyen estados históricos.']] });
    table({ head: [headings], body: data.details.length ? data.details.map(row => [row.order_id, row.emitted_at, row.citizens, row.documents, row.codes, row.procedures, row.state_label, row.amount].map(text)) : [[{ content: 'No se encontraron órdenes para estos filtros.', colSpan: 8 }]],
      columnStyles: { 0: { cellWidth: 25 }, 1: { cellWidth: 29 }, 2: { cellWidth: 47 }, 3: { cellWidth: 26 }, 4: { cellWidth: 29 }, 5: { cellWidth: 70 }, 6: { cellWidth: 25 }, 7: { cellWidth: 22, halign: 'right' } } });
    table({ theme: 'plain', body: [['Desglose por documento y procedimiento. Los conteos de órdenes no son aditivos: una orden puede tener varios procedimientos.']] });
    table({ head: [['Documento', 'Código', 'Procedimiento', 'Órdenes (no aditivas)', 'Conceptos', 'Importe (S/)']],
      body: data.breakdown.length ? data.breakdown.map(row => [row.document, row.code, row.procedure, row.orders, row.lines, row.amount].map(text)) : [[{ content: 'Sin conceptos coincidentes.', colSpan: 6 }]],
      columnStyles: { 3: { halign: 'right' }, 4: { halign: 'right' }, 5: { halign: 'right' } } });
    const pages = doc.getNumberOfPages();
    for (let page = 1; page <= pages; page++) {
      doc.setPage(page);
      doc.setFont('PTSans', 'normal');
      doc.setFontSize(11);
      doc.setTextColor(32, 107, 196);
      doc.text('SIGODT · Reporte', 12, 12);
      doc.setDrawColor(200, 210, 220);
      doc.line(12, 15, 285, 15);
      doc.setFontSize(8);
      doc.setTextColor(70, 80, 90);
      doc.text(`Página ${page} de ${pages}`, 285, 202, { align: 'right' });
    }
    return doc;
  }
  async function loadFont() {
    const response = await root.fetch('../../public/lib/report-pdf-fonts/PT_Sans-Web-Regular.ttf', { credentials: 'same-origin', cache: 'no-store' });
    if (!response.ok) throw new Error('No se pudo cargar la fuente PDF. Intente nuevamente.');
    const bytes = new Uint8Array(await response.arrayBuffer());
    let binary = '';
    for (const byte of bytes) binary += String.fromCharCode(byte);
    return root.btoa(binary);
  }
  const api = { build, filename, loadFont };
  if (typeof module !== 'undefined' && module.exports) module.exports = api;
  else root.ReportPdf = api;
})(typeof window !== 'undefined' ? window : globalThis);
