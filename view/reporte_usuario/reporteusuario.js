/* The server owns authorization, totals and pagination; the browser only renders text. */
(function (root) {
  'use strict';
  function monthRange(now) {
    const format = date => `${date.getFullYear()}-${String(date.getMonth() + 1).padStart(2, '0')}-${String(date.getDate()).padStart(2, '0')}`;
    return { start: format(new Date(now.getFullYear(), now.getMonth(), 1)), end: format(new Date(now.getFullYear(), now.getMonth() + 1, 0)) };
  }
  function reportUrl(filters, operation = 'json') {
    const params = new URLSearchParams({ op: operation });
    for (const key of ['start', 'end', 'document', 'procedure']) {
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
    const readFilters = () => ({ start: get('reportStart').value, end: get('reportEnd').value, document: get('reportDocument').value, procedure: get('reportProcedure').value });
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
        get('reportIdentity').textContent = `Emisor: ${data.emitter} · Periodo: ${filters.start} al ${filters.end} · Generado: ${data.generated_at}`;
        const summary = data.summary;
        get('reportSummary').textContent = `Total de órdenes emitidas: ${summary.orders} · Órdenes anuladas y extornadas: ${summary.cancelled} · Conceptos coincidentes: ${summary.lines} · Importe emitido: S/ ${summary.amount}`;
        table('reportBreakdown', data.breakdown.map(row => [row.document, row.code, row.procedure, row.orders, row.lines, row.amount]));
        table('reportDetails', data.details.map(row => [row.order_id, row.emitted_at, row.citizens, row.documents, row.codes, row.procedures, row.state_label, row.amount]));
        get('reportCsv').href = reportUrl(applied, 'csv');
        get('reportPrint').href = reportUrl(applied, 'print');
        get('reportPage').textContent = `Página ${page} de ${Math.max(1, Math.ceil(data.total / data.limit))} · ${data.total} órdenes`;
        get('reportPrev').disabled = page <= 1;
        get('reportNext').disabled = page * data.limit >= data.total;
        get('reportMessage').textContent = data.total ? 'Reporte actualizado.' : 'No se encontraron órdenes para estos filtros.';
        get('reportResults').hidden = false;
      } catch (error) {
        if (token === request) get('reportMessage').textContent = error.message || 'No se pudo consultar. Intente nuevamente.';
      }
    }
    form.addEventListener('submit', event => { event.preventDefault(); load(readFilters()); });
    // Changing pending filters invalidates the displayed report and any in-flight response.
    form.addEventListener('change', event => {
      request++;
      if (event.target === get('reportDocument')) get('reportProcedure').value = '';
      get('reportResults').hidden = true;
      get('reportMessage').textContent = 'Filtros modificados. Pulse Consultar para actualizar.';
    });
    get('reportPrev').addEventListener('click', () => { if (applied) load(applied, page - 1); });
    get('reportNext').addEventListener('click', () => { if (applied) load(applied, page + 1); });
    load(readFilters());
  }
  if (typeof module !== 'undefined' && module.exports) module.exports = { monthRange, reportUrl, cell, mount };
  if (root.document) mount(root.document);
})(typeof window !== 'undefined' ? window : globalThis);
