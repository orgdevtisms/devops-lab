const $ = (s, el = document) => el.querySelector(s);
const esc = s => String(s ?? '').replace(/[&<>"']/g, c => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]));
let ME = null;

async function api(r, a, { params = {}, body = null } = {}) {
  const opt = { credentials: 'same-origin', headers: {} };
  if (body) { opt.method = 'POST'; opt.headers['Content-Type'] = 'application/json'; opt.headers['X-CSRF-Token'] = ME.csrf; opt.body = JSON.stringify(body); }
  const res = await fetch('api.php?' + new URLSearchParams({ r, a, ...params }), opt);
  const j = await res.json().catch(() => ({ erro: 'Resposta inválida' }));
  if (res.status === 401) location = 'login.php';
  if (!res.ok) { const e = new Error(j.erro || 'Erro'); e.campos = j.campos; throw e; }
  return j;
}
function toast(msg) { $('#toast .toast-body').textContent = msg; bootstrap.Toast.getOrCreateInstance($('#toast')).show(); }
const brDate = v => /^\d{4}-\d{2}-\d{2}/.test(v || '') ? v.slice(8, 10) + '/' + v.slice(5, 7) + '/' + v.slice(0, 4) : (v ?? '');

const ST = ['ativo', 'manutencao', 'inativo'];
const RES = {
  veiculos: { titulo: 'Veículos', cols: [['placa', 'Placa'], ['marca', 'Marca'], ['modelo', 'Modelo'], ['ano', 'Ano'], ['km_atual', 'KM atual'], ['status', 'Status']], fields: [
    { n: 'placa', l: 'Placa', t: 'placa', req: 1 }, { n: 'renavam', l: 'RENAVAM', t: 'text' }, { n: 'chassi', l: 'Chassi', t: 'text' },
    { n: 'marca', l: 'Marca', t: 'text', req: 1 }, { n: 'modelo', l: 'Modelo', t: 'text', req: 1 }, { n: 'ano', l: 'Ano', t: 'number' }, { n: 'cor', l: 'Cor', t: 'text' },
    { n: 'combustivel', l: 'Combustível', t: 'select', o: ['gasolina', 'etanol', 'flex', 'diesel', 'eletrico', 'gnv', 'hibrido'], req: 1 },
    { n: 'km_atual', l: 'KM atual', t: 'number', req: 1 }, { n: 'status', l: 'Status', t: 'select', o: ST, req: 1 }] },
  condutores: { titulo: 'Condutores', cols: [['nome', 'Nome'], ['cpf', 'CPF'], ['cnh_categoria', 'CNH'], ['cnh_validade', 'Validade CNH'], ['status', 'Status']], fields: [
    { n: 'nome', l: 'Nome', t: 'text', req: 1 }, { n: 'cpf', l: 'CPF', t: 'cpf', req: 1 }, { n: 'cnh_numero', l: 'Nº CNH', t: 'text', req: 1 },
    { n: 'cnh_categoria', l: 'Categoria', t: 'select', o: ['A', 'B', 'C', 'D', 'E', 'AB', 'AC', 'AD', 'AE'], req: 1 }, { n: 'cnh_validade', l: 'Validade CNH', t: 'date', req: 1 },
    { n: 'telefone', l: 'Telefone', t: 'phone' }, { n: 'email', l: 'E-mail', t: 'email' }, { n: 'status', l: 'Status', t: 'select', o: ['ativo', 'inativo'], req: 1 }] },
  manutencoes: { titulo: 'Manutenções', cols: [['placa', 'Placa'], ['tipo', 'Tipo'], ['data_manutencao', 'Data'], ['custo', 'Custo'], ['proxima_data', 'Próx. data'], ['proxima_km', 'Próx. KM'], ['status', 'Status']], fields: [
    { n: 'veiculo_id', l: 'Veículo', t: 'ref', ref: 'veiculos', req: 1 }, { n: 'tipo', l: 'Tipo', t: 'select', o: ['preventiva', 'corretiva', 'revisao', 'troca_oleo', 'pneus', 'outros'], req: 1 },
    { n: 'data_manutencao', l: 'Data', t: 'date', req: 1 }, { n: 'km_manutencao', l: 'KM na manutenção', t: 'number' }, { n: 'custo', l: 'Custo (R$)', t: 'text' },
    { n: 'oficina_nome', l: 'Oficina', t: 'text' }, { n: 'oficina_cnpj', l: 'CNPJ da oficina', t: 'cnpj' },
    { n: 'proxima_data', l: 'Próxima manutenção (data)', t: 'date' }, { n: 'proxima_km', l: 'Próxima manutenção (KM)', t: 'number' },
    { n: 'status', l: 'Status', t: 'select', o: ['concluida', 'agendada'], req: 1 }, { n: 'descricao', l: 'Descrição', t: 'textarea', full: 1 }] },
  uso: { titulo: 'Uso diário', cols: [['data_uso', 'Data'], ['placa', 'Placa'], ['condutor', 'Condutor'], ['km_inicial', 'KM inicial'], ['km_final', 'KM final']], fields: [
    { n: 'veiculo_id', l: 'Veículo', t: 'ref', ref: 'veiculos', req: 1 }, { n: 'condutor_id', l: 'Condutor', t: 'ref', ref: 'condutores', req: 1 },
    { n: 'data_uso', l: 'Data de uso', t: 'date', req: 1 }, { n: 'autorizacao_id', l: 'Autorização', t: 'ref', ref: 'autorizacoes' },
    { n: 'km_inicial', l: 'KM inicial', t: 'number', req: 1 }, { n: 'km_final', l: 'KM final', t: 'number' }, { n: 'observacao', l: 'Observação', t: 'text', full: 1 }] },
  autorizacoes: { titulo: 'Autorizações', cols: [['id', 'Nº'], ['placa', 'Placa'], ['condutor', 'Condutor'], ['data_inicio', 'Início'], ['data_fim', 'Fim'], ['status', 'Status']], extra: r => `<a class="btn btn-sm btn-outline-secondary" target="_blank" href="autorizacao.php?id=${r.id}">Imprimir</a>`, fields: [
    { n: 'veiculo_id', l: 'Veículo', t: 'ref', ref: 'veiculos', req: 1 }, { n: 'condutor_id', l: 'Condutor', t: 'ref', ref: 'condutores', req: 1 },
    { n: 'data_inicio', l: 'Início', t: 'date', req: 1 }, { n: 'data_fim', l: 'Fim', t: 'date', req: 1 },
    { n: 'finalidade', l: 'Finalidade', t: 'text', req: 1, full: 1 }, { n: 'destino', l: 'Destino', t: 'text', full: 1 },
    { n: 'status', l: 'Status', t: 'select', o: ['pendente', 'aprovada', 'negada', 'encerrada'], req: 1 }] },
};

// ---------- Combobox AJAX com animação (CSS) ----------
function initCombobox(box) {
  const hid = $('input[type=hidden]', box), inp = $('.cbx-input', box), list = $('.cbx-list', box), ref = box.dataset.ref;
  let timer, seq = 0;
  const open = () => { box.classList.add('open'); load(inp.dataset.sel === inp.value ? '' : inp.value); };
  const close = () => box.classList.remove('open');
  async function load(q) {
    const my = ++seq;
    const rows = await api(ref, 'opcoes', { params: { q } }).catch(() => []);
    if (my !== seq) return;
    list.innerHTML = rows.length ? rows.map(r => `<div class="cbx-item" data-id="${r.id}">${esc(r.label)}</div>`).join('') : '<div class="cbx-empty">Nenhum resultado</div>';
  }
  inp.addEventListener('focus', open);
  inp.addEventListener('input', () => { hid.value = ''; box.classList.add('open'); clearTimeout(timer); timer = setTimeout(() => load(inp.value), 250); });
  list.addEventListener('mousedown', e => {
    const it = e.target.closest('.cbx-item'); if (!it) return;
    hid.value = it.dataset.id; inp.value = inp.dataset.sel = it.textContent; inp.classList.remove('is-invalid'); close();
  });
  inp.addEventListener('blur', () => setTimeout(close, 120));
  inp.addEventListener('keydown', e => { if (e.key === 'Escape') close(); });
}
async function presetCombobox(box, id) {
  if (!id) return;
  const rows = await api(box.dataset.ref, 'opcoes', { params: { id } }).catch(() => []);
  if (rows[0]) { $('input[type=hidden]', box).value = id; const i = $('.cbx-input', box); i.value = i.dataset.sel = rows[0].label; }
}

// ---------- Formulário ----------
function fieldHtml(f) {
  const rq = f.req ? ' <span class="text-danger">*</span>' : '', col = f.full ? 'col-12' : 'col-md-6';
  let inp;
  if (f.t === 'ref') inp = `<div class="cbx" data-ref="${f.ref}"><input type="hidden" name="${f.n}"><input class="form-control cbx-input" autocomplete="off" placeholder="Digite para buscar..."><span class="cbx-arrow">▾</span><div class="cbx-list"></div></div>`;
  else if (f.t === 'select') inp = `<select class="form-select" name="${f.n}">${f.req ? '' : '<option value=""></option>'}${f.o.map(o => `<option>${o}</option>`).join('')}</select>`;
  else if (f.t === 'textarea') inp = `<textarea class="form-control" name="${f.n}" rows="2"></textarea>`;
  else inp = `<input class="form-control" name="${f.n}" data-t="${f.t}" type="${f.t === 'date' ? 'date' : f.t === 'number' ? 'number' : 'text'}" ${f.t === 'number' ? 'min="0"' : ''}>`;
  return `<div class="${col}"><label class="form-label">${f.l}${rq}</label>${inp}<div class="invalid-feedback d-block"></div></div>`;
}
function setErr(el, msg) {
  el.classList.toggle('is-invalid', !!msg);
  const fb = el.closest('.col-md-6,.col-12')?.querySelector('.invalid-feedback'); if (fb) fb.textContent = msg || '';
}
async function openForm(key, id) {
  const def = RES[key];
  const row = id ? await api(key, 'get', { params: { id } }) : {};
  const el = document.createElement('div'); el.className = 'modal fade'; el.tabIndex = -1;
  el.innerHTML = `<div class="modal-dialog modal-lg"><form class="modal-content" novalidate><div class="modal-header"><h5 class="modal-title">${id ? 'Editar' : 'Novo'} — ${def.titulo}</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
    <div class="modal-body"><div class="alert alert-danger d-none"></div><div class="row g-3">${def.fields.map(fieldHtml).join('')}</div></div>
    <div class="modal-footer"><button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancelar</button><button class="btn btn-primary">Salvar</button></div></form></div>`;
  document.body.appendChild(el);
  const form = $('form', el), modal = new bootstrap.Modal(el); modal.show();
  el.addEventListener('hidden.bs.modal', () => el.remove());

  for (const f of def.fields) {
    const inp = form.elements[f.n]; if (!inp) continue;
    if (f.t === 'ref') { const box = inp.closest('.cbx'); initCombobox(box); presetCombobox(box, row[f.n]); continue; }
    let val = row[f.n] ?? ''; if (MASK[f.t]) val = MASK[f.t](val); inp.value = val;
    if (MASK[f.t]) inp.addEventListener('input', () => inp.value = MASK[f.t](inp.value));
    inp.addEventListener('blur', () => setErr(inp, validateField(f.t, inp.value, f.req)));
  }
  form.addEventListener('submit', async e => {
    e.preventDefault(); const body = { id }; let bad = 0;
    for (const f of def.fields) {
      const inp = form.elements[f.n], v = inp.value.trim(); body[f.n] = v;
      const target = f.t === 'ref' ? inp.closest('.cbx').querySelector('.cbx-input') : inp;
      const msg = validateField(f.t === 'ref' ? 'text' : f.t, v, f.req); setErr(target, msg); bad += msg ? 1 : 0;
    }
    if (bad) return;
    try { await api(key, 'save', { body }); modal.hide(); toast('Salvo com sucesso'); list(key, 1); }
    catch (err) {
      const a = $('.alert', el); a.textContent = err.message; a.classList.remove('d-none');
      for (const [k, m] of Object.entries(err.campos || {})) { const i = form.elements[k]; if (i) setErr(i.type === 'hidden' ? i.closest('.cbx').querySelector('.cbx-input') : i, m); }
    }
  });
}

// ---------- Listagem ----------
async function list(key, page = 1, q = '') {
  const def = RES[key], can = p => ME.perms.includes(key + '.' + p);
  const d = await api(key, 'list', { params: { p: page, q } });
  const pages = Math.max(1, Math.ceil(d.total / d.per));
  $('#view').innerHTML = `<div class="d-flex mb-3 gap-2"><h4 class="me-auto">${def.titulo}</h4>
    <input id="q" class="form-control w-25" placeholder="Buscar..." value="${esc(q)}">${can('criar') ? '<button id="novo" class="btn btn-primary">+ Novo</button>' : ''}</div>
    <div class="table-responsive"><table class="table table-hover bg-white"><thead><tr>${def.cols.map(c => `<th>${c[1]}</th>`).join('')}<th></th></tr></thead><tbody>
    ${d.rows.map(r => `<tr>${def.cols.map(c => `<td>${esc(brDate(r[c[0]]))}</td>`).join('')}<td class="text-end text-nowrap">${def.extra ? def.extra(r) : ''}
      ${can('editar') ? `<button class="btn btn-sm btn-outline-primary" data-e="${r.id}">Editar</button>` : ''}
      ${can('excluir') ? `<button class="btn btn-sm btn-outline-danger" data-d="${r.id}">Excluir</button>` : ''}</td></tr>`).join('') || `<tr><td colspan="${def.cols.length + 1}" class="text-muted text-center">Nenhum registro</td></tr>`}
    </tbody></table></div>
    <div class="d-flex justify-content-between"><small class="text-muted">${d.total} registro(s)</small><div>
      <button class="btn btn-sm btn-light" id="prev" ${page <= 1 ? 'disabled' : ''}>‹</button> ${page}/${pages} <button class="btn btn-sm btn-light" id="next" ${page >= pages ? 'disabled' : ''}>›</button></div></div>`;
  let t; $('#q').oninput = e => { clearTimeout(t); t = setTimeout(() => list(key, 1, e.target.value), 300); };
  $('#novo')?.addEventListener('click', () => openForm(key));
  $('#prev').onclick = () => list(key, page - 1, q); $('#next').onclick = () => list(key, page + 1, q);
  $('#view').querySelectorAll('[data-e]').forEach(b => b.onclick = () => openForm(key, +b.dataset.e));
  $('#view').querySelectorAll('[data-d]').forEach(b => b.onclick = async () => {
    if (!confirm('Excluir este registro?')) return;
    try { await api(key, 'delete', { body: { id: +b.dataset.d } }); toast('Excluído'); list(key, page, q); } catch (e) { toast(e.message); }
  });
}

// ---------- Dashboard ----------
async function dashboard() {
  const d = await api('dashboard', 'get');
  const badge = s => s === 'vencida' ? '<span class="badge text-bg-danger">Vencida</span>' : '<span class="badge text-bg-warning">Próxima</span>';
  $('#view').innerHTML = `<h4 class="mb-3">Painel</h4><div class="row g-3 mb-4">
    <div class="col-md-4"><div class="card"><div class="card-body"><div class="text-muted">KM rodados no mês</div><div class="fs-3">${d.km_mes.toLocaleString('pt-BR')}</div></div></div></div>
    <div class="col-md-4"><div class="card"><div class="card-body"><div class="text-muted">Autorizações pendentes</div><div class="fs-3">${d.pendentes}</div></div></div></div>
    <div class="col-md-4"><div class="card"><div class="card-body"><div class="text-muted">CNHs vencendo (30 dias)</div><div class="fs-3">${d.cnh.length}</div></div></div></div></div>
    <h6>Próximas manutenções</h6><ul class="list-group mb-4">${d.manutencoes.map(m => `<li class="list-group-item d-flex justify-content-between">
      <span><b>${esc(m.placa)}</b> ${esc(m.modelo)} — ${esc(m.tipo)}<br><small class="text-muted">${m.proxima_data ? 'até ' + brDate(m.proxima_data) : ''} ${m.proxima_km ? '· ' + m.proxima_km + ' km (atual ' + m.km_atual + ')' : ''}</small></span>${badge(m.situacao)}</li>`).join('')
      + d.agendadas.map(m => `<li class="list-group-item"><b>${esc(m.placa)}</b> — ${esc(m.tipo)} agendada para ${brDate(m.data_manutencao)}</li>`).join('')
      || '<li class="list-group-item text-muted">Nenhum aviso</li>'}</ul>
    <h6>CNHs a vencer</h6><ul class="list-group">${d.cnh.map(c => `<li class="list-group-item">${esc(c.nome)} — vence em ${brDate(c.cnh_validade)}</li>`).join('') || '<li class="list-group-item text-muted">Nenhuma</li>'}</ul>`;
}

// ---------- Init ----------
(async () => {
  ME = await api('me', 'get');
  $('#who').textContent = `${ME.user.nome} · IP ${ME.ip} · ${ME.local}`;
  const items = []; if (ME.perms.includes('dashboard.ver')) items.push(['dashboard', 'Painel']);
  for (const k of Object.keys(RES)) if (ME.perms.includes(k + '.ver')) items.push([k, RES[k].titulo]);
  $('#menu').innerHTML = items.map(i => `<a href="#" class="nav-link" data-k="${i[0]}">${i[1]}</a>`).join('');
  $('#menu').addEventListener('click', e => {
    const a = e.target.closest('[data-k]'); if (!a) return; e.preventDefault();
    document.querySelectorAll('#menu .nav-link').forEach(n => n.classList.toggle('active', n === a));
    a.dataset.k === 'dashboard' ? dashboard() : list(a.dataset.k);
  });
  $('#menu .nav-link')?.click();
})();
