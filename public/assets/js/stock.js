/** Atualiza o estoque exibido no catálogo (vendedor) a cada 60 s, usando a sessão web. */
document.addEventListener('DOMContentLoaded', () => {
  const els = document.querySelectorAll('.wd-stock'); if (!els.length) return;
  setInterval(async () => { try { const r = await WD.get('/ajax/estoque'); if (r.success) els.forEach(el => { const v = r.data[el.dataset.id]; if (v !== undefined) el.textContent = v; }); } catch (e) {} }, 60000);
});
