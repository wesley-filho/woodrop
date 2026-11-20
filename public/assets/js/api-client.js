/** Cliente fetch para chamadas assíncronas (AJAX) com CSRF e JSON padronizado { success, data, message }. */
const WD = {
  base: document.body.dataset.base || '',
  async req(path, opts = {}) {
    const o = Object.assign({ method: 'GET', headers: {} }, opts);
    if (o.method !== 'GET') o.headers['X-CSRF-Token'] = document.body.dataset.csrf;
    const r = await fetch(WD.base + path, o);
    return r.json();
  },
  get(p) { return WD.req(p); },
  post(p) { return WD.req(p, { method: 'POST' }); },
};
