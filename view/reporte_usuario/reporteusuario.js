/* The server owns authorization, totals and pagination; the browser only renders text. */
(function (root) {
  'use strict';
  function getSummaryCopy(state) {
    switch (String(state ?? 'todos')) {
      case '4':
        return {
          title: 'Resumen de órdenes pagadas',
          ordersLabel: 'Total de órdenes pagadas',
          linesLabel: 'Total de tasas pagadas',
          amountLabel: 'Importe pagado',
          warningStrong: 'Importe efectivamente recaudado en caja.',
          warningDesc: 'Los filtros cuentan cada orden pagada coincidente y suman sus conceptos cobrados en caja.'
        };
      case '1':
      case '2':
        return {
          title: 'Resumen de órdenes pendientes de pago',
          ordersLabel: 'Total de órdenes giradas',
          linesLabel: 'Total de tasas giradas',
          amountLabel: 'Importe pendiente',
          warningStrong: 'El importe girado no equivale a dinero recaudado.',
          warningDesc: 'Muestra órdenes emitidas pendientes de pago en caja. Los filtros suman solo sus conceptos coincidentes.'
        };
      case 'anulados':
      case '0':
      case '6':
        return {
          title: 'Resumen de órdenes anuladas y extornadas',
          ordersLabel: 'Total de órdenes anuladas/extornadas',
          linesLabel: 'Total de tasas anuladas/extornadas',
          amountLabel: 'Importe anulado / extornado',
          warningStrong: 'Las órdenes anuladas o extornadas no constituyen recaudación efectiva.',
          warningDesc: 'Los filtros cuentan cada orden anulada o extornada coincidente y suman sus conceptos correspondientes.'
        };
      case 'emitidos':
        return {
          title: 'Resumen de emisiones',
          ordersLabel: 'Total de órdenes emitidas',
          linesLabel: 'Total de tasas emitidas',
          amountLabel: 'Importe emitido',
          warningStrong: 'El importe emitido no equivale a dinero recaudado.',
          warningDesc: 'Los filtros cuentan cada orden coincidente una vez y suman solo sus conceptos coincidentes. Incluye órdenes giradas y pagadas; los giros pendientes no constituyen dinero recaudado.'
        };
      case 'todos':
      default:
        return {
          title: 'Resumen general de órdenes',
          ordersLabel: 'Total de órdenes',
          linesLabel: 'Total de tasas',
          amountLabel: 'Importe total',
          warningStrong: 'El importe emitido no equivale en su totalidad a dinero recaudado.',
          warningDesc: 'Los filtros cuentan cada orden coincidente una vez y suman solo sus conceptos coincidentes. Se incluyen órdenes pagadas, giradas, anuladas, extornadas y estados históricos.'
        };
    }
  }
  function monthRange(now) {
    const format = date => `${date.getFullYear()}-${String(date.getMonth() + 1).padStart(2, '0')}-${String(date.getDate()).padStart(2, '0')}`;
    return { start: format(new Date(now.getFullYear(), now.getMonth(), 1)), end: format(new Date(now.getFullYear(), now.getMonth() + 1, 0)) };
  }
  function reportUrl(filters, operation = 'json') {
    const params = new URLSearchParams({ op: operation });
    for (const key of ['start', 'end', 'state', 'document', 'procedure']) {
      if (filters[key] !== undefined && filters[key] !== '') params.set(key, filters[key]);
    }
    if (operation === 'json') params.set('page', filters.page || 1);
    return `../../controller/reporte_usuario.php?${params}`;
  }
  function cell(row, value, doc) {
    const td = doc.createElement('td');
    td.textContent = Array.isArray(value) ? value.join(' / ') : String(value ?? '');
    row.appendChild(td);
  }
  function mount(doc) {
    const get = id => doc.getElementById(id);
    const form = get('reportForm');
    if (!form) return;
    const range = monthRange(new Date());
    get('reportStart').value = range.start;
    get('reportEnd').value = range.end;
    let page = 1;
    let applied = null;
    let request = 0;
    let pdfRequest = 0;
    const pdfButton = get('reportPdf');
    const invalidatePdf = () => {
      pdfRequest++;
      pdfButton.disabled = true;
      pdfButton.textContent = 'Descargar PDF';
    };
    const readFilters = () => ({
      start: get('reportStart').value,
      end: get('reportEnd').value,
      state: get('reportState') ? get('reportState').value : 'todos',
      document: get('reportDocument').value,
      procedure: get('reportProcedure').value
    });
    const table = (id, rows) => {
      const body = get(id);
      body.replaceChildren();
      for (const values of rows) {
        const tr = doc.createElement('tr');
        for (const value of values) cell(tr, value, doc);
        body.appendChild(tr);
      }
    };
    const options = (id, values, selected) => {
      const select = get(id);
      select.replaceChildren();
      for (const item of [{ id: '', label: 'Todos' }, ...values]) {
        const option = doc.createElement('option');
        option.value = item.id;
        option.textContent = item.label;
        select.appendChild(option);
      }
      // Preserve a stale selection explicitly rather than silently changing the filter.
      if (selected && !values.some(item => String(item.id) === selected)) {
        const option = doc.createElement('option');
        option.value = selected;
        option.textContent = `Sin coincidencias (ID ${selected})`;
        select.appendChild(option);
      }
      select.value = selected;
    };
    async function load(filters, wantedPage = 1) {
      const token = ++request;
      invalidatePdf();
      get('reportResults').hidden = true;
      get('reportMessage').textContent = 'Consultando…';
      get('reportPrev').disabled = true;
      get('reportNext').disabled = true;
      try {
        const response = await root.fetch(reportUrl({ ...filters, page: wantedPage }), { credentials: 'same-origin', cache: 'no-store' });
        const data = await response.json();
        if (token !== request) return;
        if (!response.ok || data.error) throw new Error(data.error || 'No se pudo consultar.');
        applied = { ...filters };
        page = data.page;
        options('reportDocument', data.options.documents, filters.document);
        options('reportProcedure', data.options.procedures, filters.procedure);
        get('reportIdentity').textContent = `Emisor: ${data.emitter} · Periodo: ${filters.start} al ${filters.end} · Estado: ${data.state_label || 'Todos'} · Generado: ${data.generated_at}`;
        const copy = getSummaryCopy(filters.state);
        const titleEl = get('reportSummaryTitle');
        if (titleEl) titleEl.textContent = copy.title;
        const ordersLbl = get('reportOrdersLabel');
        if (ordersLbl) ordersLbl.textContent = copy.ordersLabel;
        const linesLbl = get('reportLinesLabel');
        if (linesLbl) linesLbl.textContent = copy.linesLabel;
        const amountLbl = get('reportAmountLabel');
        if (amountLbl) amountLbl.textContent = copy.amountLabel;
        const warnStrong = get('reportWarningStrong');
        if (warnStrong) warnStrong.textContent = copy.warningStrong;
        const warnDesc = get('reportWarningDesc');
        if (warnDesc) warnDesc.textContent = copy.warningDesc;
        const summary = data.summary;
        get('reportOrders').textContent = String(summary.orders);
        get('reportCancelled').textContent = String(summary.cancelled);
        get('reportLines').textContent = String(summary.lines);
        get('reportAmount').textContent = `S/ ${summary.amount}`;
        table('reportBreakdown', data.breakdown.map(row => [row.document, row.code, row.procedure, row.orders, row.lines, row.amount]));
        table('reportDetails', data.details.map(row => [row.order_id, row.emitted_at, row.citizens, row.documents, row.codes, row.procedures, row.state_label, row.amount]));
        get('reportPrint').href = reportUrl(applied, 'print');
        get('reportPage').textContent = `Página ${page} de ${Math.max(1, Math.ceil(data.total / data.limit))} · ${data.total} órdenes`;
        get('reportPrev').disabled = page <= 1;
        get('reportNext').disabled = page * data.limit >= data.total;
        get('reportMessage').textContent = data.total ? 'Reporte actualizado.' : 'No se encontraron órdenes para estos filtros.';
        get('reportResults').hidden = false;
        pdfButton.disabled = false;
      } catch (error) {
        if (token === request) get('reportMessage').textContent = error.message || 'No se pudo consultar. Intente nuevamente.';
      }
    }
    pdfButton.addEventListener('click', async () => {
      if (!applied || pdfButton.disabled) return;
      const token = ++pdfRequest;
      const reportToken = request;
      const filters = { ...applied };
      const current = () => token === pdfRequest && reportToken === request;
      pdfButton.disabled = true;
      pdfButton.textContent = 'Generando PDF…';
      get('reportMessage').textContent = 'Generando PDF completo…';
      try {
        if (!root.ReportPdf) throw new Error('No se pudo cargar PDF. Recargue la página e intente nuevamente.');
        const response = await root.fetch(reportUrl(filters, 'pdf'), { credentials: 'same-origin', cache: 'no-store' });
        const data = await response.json();
        if (!current()) return;
        if (!response.ok || data.error) {
          if (response.status === 401) {
            applied = null;
            get('reportResults').hidden = true;
          }
          throw new Error(data.error || 'No se pudo generar el PDF. Intente nuevamente.');
        }
        const font = await root.ReportPdf.loadFont();
        if (!current()) return;
        const pdf = root.ReportPdf.build(data, filters, font);
        if (!current()) return;
        pdf.save(root.ReportPdf.filename(filters));
        get('reportMessage').textContent = 'PDF generado.';
      } catch (error) {
        if (current()) get('reportMessage').textContent = error.message || 'No se pudo generar el PDF. Intente nuevamente.';
      } finally {
        if (current()) {
          pdfButton.disabled = !applied;
          pdfButton.textContent = 'Descargar PDF';
        }
      }
    });
    form.addEventListener('submit', event => { event.preventDefault(); load(readFilters()); });
    // Changing pending filters invalidates the displayed report and any in-flight response.
    form.addEventListener('change', event => {
      request++;
      invalidatePdf();
      if (event.target === get('reportDocument')) get('reportProcedure').value = '';
      get('reportResults').hidden = true;
      get('reportMessage').textContent = 'Filtros modificados. Pulse Consultar para actualizar.';
    });
    get('reportPrev').addEventListener('click', () => { if (applied) load(applied, page - 1); });
    get('reportNext').addEventListener('click', () => { if (applied) load(applied, page + 1); });
    load(readFilters());
  }
  if (typeof module !== 'undefined' && module.exports) module.exports = { monthRange, reportUrl, cell, mount, getSummaryCopy };
  if (root) root.getSummaryCopy = getSummaryCopy;
  if (root.document) mount(root.document);
})(typeof window !== 'undefined' ? window : globalThis);
