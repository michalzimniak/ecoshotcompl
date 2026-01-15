(function () {
  'use strict';

  const csrf = window.PANEL?.csrf;
  const apiUrl = window.PANEL?.apiUrl;

  const defaultSelect = document.getElementById('defaultSelect');
  const rulesJson = document.getElementById('rulesJson');
  const status = document.getElementById('status');
  const reloadBtn = document.getElementById('reloadBtn');
  const saveBtn = document.getElementById('saveBtn');
  const regenerateBtn = document.getElementById('regenerateBtn');

  function setStatus(msg) {
    if (status) status.textContent = msg || '';
  }

  async function uiError(message) {
    const msg = message || 'Wystąpił błąd.';
    if (window.Swal?.fire) {
      await window.Swal.fire({ icon: 'error', title: 'Błąd', text: msg, confirmButtonText: 'OK' });
      return;
    }
    alert(msg);
  }

  async function uiOk(message) {
    const msg = message || 'Gotowe.';
    if (window.Swal?.fire) {
      await window.Swal.fire({ icon: 'success', title: 'OK', text: msg, confirmButtonText: 'OK', timer: 1200, showConfirmButton: false });
      return;
    }
  }

  async function apiGet() {
    const res = await fetch(apiUrl, { headers: { 'Accept': 'application/json' } });
    const data = await res.json().catch(() => null);
    if (!res.ok || !data || !data.ok) throw new Error(data?.error || 'Błąd');
    return data;
  }

  async function apiPost(action, payload) {
    const form = new FormData();
    form.append('action', action);
    form.append('csrf', csrf);
    Object.entries(payload || {}).forEach(([k, v]) => form.append(k, v));

    const res = await fetch(apiUrl, {
      method: 'POST',
      body: form,
      headers: { 'Accept': 'application/json', 'X-CSRF-Token': csrf }
    });
    const data = await res.json().catch(() => null);
    if (!res.ok || !data || !data.ok) throw new Error(data?.error || 'Błąd');
    return data;
  }

  function fillDefaultSelect(allowed, current) {
    defaultSelect.innerHTML = allowed.map(c => {
      const sel = c === current ? 'selected' : '';
      return `<option ${sel}>${c}</option>`;
    }).join('');
  }

  async function load() {
    try {
      reloadBtn && (reloadBtn.disabled = true);
      setStatus('Ładowanie...');
      const data = await apiGet();
      fillDefaultSelect(data.allowed || [], data.default);
      rulesJson.value = JSON.stringify(data.rules || {}, null, 2);
      setStatus('');
    } catch (e) {
      setStatus('');
      await uiError(e.message || String(e));
    } finally {
      reloadBtn && (reloadBtn.disabled = false);
    }
  }

  reloadBtn?.addEventListener('click', load);

  saveBtn?.addEventListener('click', async () => {
    try {
      saveBtn.disabled = true;
      setStatus('Zapisywanie...');
      const parsed = JSON.parse(rulesJson.value || '{}');
      await apiPost('save', {
        default: defaultSelect.value,
        rules_json: JSON.stringify(parsed)
      });
      setStatus('');
      await uiOk('Zapisano reguły.');
    } catch (e) {
      setStatus('');
      await uiError(e.message || String(e));
    } finally {
      saveBtn.disabled = false;
    }
  });

  regenerateBtn?.addEventListener('click', async () => {
    try {
      regenerateBtn.disabled = true;
      setStatus('Przeliczanie...');
      const res = await apiPost('regenerate', {});
      setStatus('');
      await uiOk(`Przeliczono. Zaktualizowano: ${res.updated ?? 0}`);
    } catch (e) {
      setStatus('');
      await uiError(e.message || String(e));
    } finally {
      regenerateBtn.disabled = false;
    }
  });

  load();
})();
