/* No server-supplied string is interpreted as HTML. */
(function (root) {
  'use strict';
  const labels = ['Anulado', 'Pendiente', 'Girado', 'Improcedente', 'Pagado', 'Usado', 'Extornado'];
  const colors = ['danger', 'warning', 'success', 'danger', 'primary', 'purple', 'secondary'];
  function state(value) {
    const known = value !== null && value !== '' && /^[0-6]$/.test(String(value));
    return known ? { label: labels[Number(value)], color: colors[Number(value)] }
      : { label: 'Desconocido (' + (value == null || value === '' ? '—' : String(value)) + ')', color: 'secondary' };
  }
  function cell(doc, row, text) {
    const td = doc.createElement('td');
    // Omitted text denotes a component cell; empty scalar values retain their placeholder.
    if (arguments.length > 2) td.textContent = text == null || text === '' ? '—' : String(text);
    row.appendChild(td);
    return td;
  }
  function button(doc, td, label, action) {
    const btn = doc.createElement('button');
    btn.type = 'button';
    btn.className = 'btn btn-outline-primary btn-sm';
    btn.textContent = label;
    btn.addEventListener('click', action);
    td.appendChild(btn);
  }
  function empty(doc, tbody, columns, text) {
    tbody.replaceChildren();
    const tr = doc.createElement('tr');
    const td = cell(doc, tr, text);
    td.colSpan = columns;
    td.className = 'text-center text-secondary py-4';
    tbody.appendChild(tr);
  }
  function renderEntities(doc, tbody, rows, open) {
    tbody.replaceChildren();
    if (!rows.length) { empty(doc, tbody, 5, 'No se encontraron entidades.'); return; }
    rows.forEach(row => {
      const tr = doc.createElement('tr');
      cell(doc, tr, row.entity_type === 'ciudadano' ? 'Ciudadano' : 'Empresa');
      cell(doc, tr, row.nombre);
      cell(doc, tr, (row.documento || 'Sin documento') + ' · ' + row.entity_key);
      cell(doc, tr, row.ordenes);
      button(doc, cell(doc, tr), 'Ver historial', () => open(row));
      tbody.appendChild(tr);
    });
  }
  function renderOrders(doc, tbody, rows) {
    tbody.replaceChildren();
    if (!rows.length) { empty(doc, tbody, 7, 'No se encontraron órdenes para esta entidad.'); return; }
    rows.forEach(row => {
      const tr = doc.createElement('tr');
      ['ogciud_id', 'fecha', 'hora', 'recibo_nro'].forEach(key => cell(doc, tr, row[key]));
      cell(doc, tr, 'S/ ' + Number(row.importe).toFixed(2)).className = 'text-end fw-bold';
      const status = state(row.orden_est);
      const badge = doc.createElement('span');
      badge.className = 'badge bg-' + status.color + ' text-' + status.color + '-fg';
      badge.textContent = status.label;
      cell(doc, tr).appendChild(badge);
      button(doc, cell(doc, tr), 'Ver / Imprimir', () => printOrder(doc, row.ogciud_id));
      tbody.appendChild(tr);
    });
  }
  function printOrder(doc, id) {
    if (typeof id !== 'string' && !(typeof id === 'number' && Number.isSafeInteger(id) && id > 0)) { return; }
    const value = String(id);
    // Generated sequences are padded to at least six digits; keep existing numeric IDs compatible.
    if (value.trim() !== value || !/^(?:[1-9][0-9]*|[0-9]{6,}-[0-9]{4})$/.test(value) || /^0+-/.test(value)) { return; }
    const form = doc.createElement('form');
    form.method = 'post';
    form.action = '../../controller/rc.php?op=imprimirxid';
    form.target = '_blank';
    const input = doc.createElement('input');
    input.type = 'hidden';
    input.name = 'ogciud_id';
    input.value = value;
    form.appendChild(input);
    doc.body.appendChild(form);
    form.submit();
    form.remove();
  }
  function requestGate() {
    let version = 0;
    let controller = null;
    function invalidate() {
      version += 1;
      if (controller) controller.abort();
      controller = null;
    }
    return {
      invalidate,
      async run(work) {
        invalidate();
        const token = version;
        controller = typeof root.AbortController === 'function' ? new root.AbortController() : null;
        try {
          const data = await work(controller ? controller.signal : undefined);
          return { current: token === version, data };
        }
        catch (error) { return { current: token === version, error }; }
      }
    };
  }
  async function request(op, parameters, signal) {
    const response = await root.fetch('../../controller/ordengiro.php?op=' + op, {
      method: 'POST', headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
      body: new URLSearchParams(parameters).toString(), credentials: 'same-origin', signal
    });
    if (!response.ok) {
      throw new Error(response.status === 401 ? 'Sesión no válida. Inicie sesión nuevamente.' : 'No se pudo realizar la consulta. Intente nuevamente.');
    }
    const data = await response.json();
    if (!data || !Array.isArray(data.data) || !Number.isInteger(data.total) || !Number.isInteger(data.page)) {
      throw new Error('Respuesta no válida. Intente nuevamente.');
    }
    return data;
  }
  function mount(doc) {
    const get = id => doc.getElementById(id);
    const entityGate = requestGate();
    const historyGate = requestGate();
    let timer = null;
    let composing = false;
    let search = '';
    let selected = null;
    let entities = null;
    let history = null;
    const message = (text, error = false) => {
      get('nombreMessage').textContent = text;
      get('nombreMessage').className = 'alert mb-3 alert-' + (error ? 'danger' : 'info');
    };
    function pager(prefix, data, busy = false) {
      get(prefix + 'Prev').disabled = busy || !data || data.page <= 1;
      get(prefix + 'Next').disabled = busy || !data || data.page * data.limit >= data.total;
      get(prefix + 'Page').textContent = data ? 'Página ' + data.page + ' de ' + Math.max(1, Math.ceil(data.total / data.limit)) + ' · ' + data.total + ' resultados' : '';
    }
    function back() {
      historyGate.invalidate();
      selected = null;
      get('nombreHistory').hidden = true;
      get('nombreResults').hidden = false;
      get('nombreHistory').setAttribute('aria-busy', 'false');
      get('nombreResults').setAttribute('aria-busy', 'false');
      if (entities) {
        renderEntities(doc, get('nombreEntities'), entities.data, row => {
          selected = row;
          history = null;
          loadHistory(1);
        });
      } else {
        empty(doc, get('nombreEntities'), 5, 'Ingrese un nombre y haga clic en Buscar.');
      }
      pager('entities', entities);
      message(entities ? entities.total + ' entidades encontradas. Seleccione un historial.' : 'Ingrese un nombre para buscar.');
    }
    async function loadEntities(page) {
      historyGate.invalidate();
      selected = null;
      get('nombreHistory').hidden = true;
      get('nombreResults').hidden = false;
      pager('entities', entities, true);
      empty(doc, get('nombreEntities'), 5, 'Buscando…');
      get('nombreResults').setAttribute('aria-busy', 'true');
      message('Buscando entidades…');
      const result = await entityGate.run(signal => request('buscar_entidades_nombre', { search, page, limit: 10 }, signal));
      if (!result.current) { return; }
      get('nombreResults').setAttribute('aria-busy', 'false');
      if (result.error) {
        empty(doc, get('nombreEntities'), 5, 'No se pudo cargar la búsqueda. Vuelva a buscar.');
        pager('entities', null);
        message(result.error.message, true);
        return;
      }
      entities = result.data;
      renderEntities(doc, get('nombreEntities'), entities.data, row => {
        selected = row;
        history = null;
        loadHistory(1);
      });
      pager('entities', entities);
      message(entities.total + ' entidades encontradas. Los totales incluyen todos los estados y fechas.');
    }
    async function loadHistory(page) {
      get('nombreHistory').hidden = false;
      get('nombreResults').hidden = true;
      get('historyIdentity').textContent = selected.nombre + ' · ' + (selected.documento || 'Sin documento') + ' · ' + selected.entity_key;
      pager('history', history, true);
      empty(doc, get('nombreOrders'), 7, 'Cargando historial…');
      get('nombreHistory').setAttribute('aria-busy', 'true');
      message('Cargando historial completo…');
      const result = await historyGate.run(signal => request('historial_entidad', {
        entity_type: selected.entity_type, entity_key: selected.entity_key, page, limit: 10
      }, signal));
      if (!result.current) { return; }
      get('nombreHistory').setAttribute('aria-busy', 'false');
      if (result.error) {
        empty(doc, get('nombreOrders'), 7, 'No se pudo cargar el historial. Regrese a resultados e intente nuevamente.');
        pager('history', null);
        message(result.error.message, true);
        return;
      }
      history = result.data;
      renderOrders(doc, get('nombreOrders'), history.data);
      pager('history', history);
      message(history.total + ' órdenes en el historial completo.');
    }
    function cancelTimer() {
      if (timer !== null) root.clearTimeout(timer);
      timer = null;
    }
    function clearSearch(text) {
      entityGate.invalidate();
      historyGate.invalidate();
      selected = null;
      entities = null;
      history = null;
      get('nombreHistory').hidden = true;
      get('nombreResults').hidden = false;
      get('historyIdentity').textContent = '';
      get('nombreResults').setAttribute('aria-busy', 'false');
      get('nombreHistory').setAttribute('aria-busy', 'false');
      empty(doc, get('nombreEntities'), 5, text);
      get('nombreOrders').replaceChildren();
      pager('entities', null);
      pager('history', null);
      message(text);
    }
    function submitSearch(value) {
      cancelTimer();
      search = value;
      entities = null;
      history = null;
      loadEntities(1);
    }
    function edited() {
      cancelTimer();
      const value = get('nombreSearch').value.trim();
      const eligible = Array.from(value).length >= 3;
      clearSearch(eligible ? 'Espere un momento o pulse Buscar para consultar.'
        : 'Ingrese al menos 3 caracteres para buscar automáticamente, o pulse Buscar para un texto más corto.');
      if (!composing && eligible) {
        timer = root.setTimeout(() => { timer = null; submitSearch(value); }, 400);
      }
    }
    get('nombreSearch').addEventListener('input', edited);
    get('nombreSearch').addEventListener('compositionstart', () => { composing = true; edited(); });
    get('nombreSearch').addEventListener('compositionend', () => { composing = false; edited(); });
    get('nombreForm').addEventListener('submit', event => {
      event.preventDefault();
      cancelTimer();
      if (composing || event.isComposing) return;
      const value = get('nombreSearch').value.trim();
      if (!value) { clearSearch('Ingrese un nombre válido.'); return; }
      submitSearch(value);
    });
    get('historyBack').addEventListener('click', back);
    ['entities', 'history'].forEach(prefix => {
      ['Prev', 'Next'].forEach(direction => {
        get(prefix + direction).addEventListener('click', () => {
          const data = prefix === 'entities' ? entities : history;
          if (data) { (prefix === 'entities' ? loadEntities : loadHistory)(data.page + (direction === 'Prev' ? -1 : 1)); }
        });
      });
    });
    empty(doc, get('nombreEntities'), 5, 'Ingrese un nombre y haga clic en Buscar.');
  }
  const api = { state, renderEntities, renderOrders, printOrder, requestGate, mount };
  if (typeof module !== 'undefined' && module.exports) { module.exports = api; }
  else if (root.document) { mount(root.document); }
})(typeof globalThis !== 'undefined' ? globalThis : this);
