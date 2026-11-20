document.addEventListener('DOMContentLoaded', () => {
  // Toasts (mensagens flash)
  document.querySelectorAll('#toasts .toast').forEach(t => new bootstrap.Toast(t).show());
  // Confirmações em formulários marcados com data-confirm
  document.querySelectorAll('form[data-confirm]').forEach(f => f.addEventListener('submit', e => { if (!confirm(f.dataset.confirm)) e.preventDefault(); }));
  // Sino de notificações (RF07/RF08)
  const bell = document.getElementById('bell');
  if (bell) {
    const count = document.getElementById('bell-count'), list = document.getElementById('bell-list');
    const load = async () => {
      try {
        const r = await WD.get('/notificacoes'); if (!r.success) return;
        count.textContent = r.data.nao_lidas; count.classList.toggle('d-none', r.data.nao_lidas === 0);
        list.innerHTML = r.data.itens.length ? '' : '<li class="text-muted small px-2">Sem notificações.</li>';
        r.data.itens.forEach(n => { const li = document.createElement('li'); li.className = 'px-2 py-1 border-bottom small' + (n.lida == 0 ? ' fw-bold' : '');
          const t = document.createElement('div'); t.textContent = n.titulo; const m = document.createElement('div'); m.className = 'text-muted fw-normal'; m.textContent = n.mensagem || ''; li.append(t, m); list.appendChild(li); });
      } catch (e) {}
    };
    load(); setInterval(load, 60000);
    bell.addEventListener('click', () => setTimeout(async () => { await WD.post('/notificacoes/lidas'); count.classList.add('d-none'); }, 1500));
  }
});
