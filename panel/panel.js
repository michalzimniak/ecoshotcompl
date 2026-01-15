(function () {
  'use strict';

  const csrf = window.PANEL?.csrf;
  const apiUrl = window.PANEL?.apiUrl;

  const grid = document.getElementById('grid');
  const refreshBtn = document.getElementById('refreshBtn');
  const uploadBtn = document.getElementById('uploadBtn');
  const uploadInput = document.getElementById('uploadInput');
  const uploadStatus = document.getElementById('uploadStatus');
  const searchInput = document.getElementById('searchInput');
  const countInfo = document.getElementById('countInfo');

  if (!grid || !apiUrl) return;

  let allFiles = [];
  let allowedCategories = [];
  let defaultCategory = '';
  let renameTarget = null;
  let lightbox = null;

  const renameModalEl = document.getElementById('renameModal');
  const renameInput = document.getElementById('renameInput');
  const renameSaveBtn = document.getElementById('renameSaveBtn');
  const renameError = document.getElementById('renameError');
  const renameModal = renameModalEl ? new bootstrap.Modal(renameModalEl) : null;

  function setUploadStatus(msg) {
    if (!uploadStatus) return;
    uploadStatus.textContent = msg || '';
  }

  function extOf(name) {
    const idx = name.lastIndexOf('.');
    return idx >= 0 ? name.slice(idx + 1) : '';
  }

  function baseOf(name) {
    const idx = name.lastIndexOf('.');
    return idx >= 0 ? name.slice(0, idx) : name;
  }

  function escapeHtml(s) {
    return String(s)
      .replaceAll('&', '&amp;')
      .replaceAll('<', '&lt;')
      .replaceAll('>', '&gt;')
      .replaceAll('"', '&quot;')
      .replaceAll("'", '&#039;');
  }

  async function uiAlertError(message) {
    const msg = message || 'Wystąpił błąd.';
    if (window.Swal && typeof window.Swal.fire === 'function') {
      await window.Swal.fire({
        icon: 'error',
        title: 'Błąd',
        text: msg,
        confirmButtonText: 'OK'
      });
      return;
    }
    alert(msg);
  }

  async function uiConfirmDelete(fileName) {
    const text = `Usunąć plik: ${fileName}?`;
    if (window.Swal && typeof window.Swal.fire === 'function') {
      const res = await window.Swal.fire({
        icon: 'warning',
        title: 'Potwierdź usunięcie',
        text,
        showCancelButton: true,
        confirmButtonText: 'Usuń',
        cancelButtonText: 'Anuluj',
        confirmButtonColor: '#dc3545'
      });
      return !!res.isConfirmed;
    }
    return confirm(text);
  }

  function render(files) {
    const q = (searchInput?.value || '').trim().toLowerCase();
    const filtered = q ? files.filter(f => String(f.name).toLowerCase().includes(q)) : files;

    countInfo.textContent = `${filtered.length} / ${files.length}`;

    grid.innerHTML = filtered.map(f => {
      const name = escapeHtml(f.name);
      const url = f.url;
      const currentCat = escapeHtml(f.category || defaultCategory || '');
      const options = (allowedCategories || []).map(c => {
        const esc = escapeHtml(c);
        const sel = c === (f.category || defaultCategory) ? 'selected' : '';
        return `<option ${sel} value="${esc}">${esc}</option>`;
      }).join('');
      return `
        <div class="col-12 col-sm-6 col-lg-4 col-xxl-3">
          <div class="card shadow-sm">
            <a class="glightbox" href="${url}" data-gallery="portfolio" data-title="${name}">
              <img class="thumb" src="${url}" alt="${name}">
            </a>
            <div class="card-body">
              <div class="small filename mb-2">${name}</div>
              <div class="mb-2">
                <select class="form-select form-select-sm" data-action="category" data-name="${name}" aria-label="Kategoria">
                  ${options}
                </select>
                <div class="form-text">Aktualnie: <strong>${currentCat}</strong></div>
              </div>
              <div class="d-flex gap-2">
                <button class="btn btn-outline-primary btn-sm" data-action="rename" data-name="${name}">Zmień nazwę</button>
                <button class="btn btn-outline-danger btn-sm ms-auto" data-action="delete" data-name="${name}">Usuń</button>
              </div>
            </div>
          </div>
        </div>
      `;
    }).join('');

    if (window.GLightbox) {
      try {
        if (lightbox && typeof lightbox.destroy === 'function') {
          lightbox.destroy();
        }
      } catch (_) {
        // ignore
      }
      lightbox = window.GLightbox({ selector: '.glightbox' });
    }
  }

  async function apiGetList() {
    const res = await fetch(apiUrl, { headers: { 'Accept': 'application/json' } });
    const data = await res.json().catch(() => null);
    if (!res.ok || !data || !data.ok) {
      throw new Error(data?.error || 'Błąd pobierania listy');
    }
    allowedCategories = data.allowedCategories || [];
    defaultCategory = data.defaultCategory || '';
    return data.files || [];
  }

  async function apiPost(action, payload) {
    const form = new FormData();
    form.append('action', action);
    form.append('csrf', csrf);
    Object.entries(payload || {}).forEach(([k, v]) => {
      if (v !== undefined && v !== null) form.append(k, v);
    });

    const res = await fetch(apiUrl, {
      method: 'POST',
      body: form,
      headers: { 'Accept': 'application/json', 'X-CSRF-Token': csrf }
    });

    const data = await res.json().catch(() => null);
    if (!res.ok || !data || !data.ok) {
      throw new Error(data?.error || 'Błąd');
    }
    return data;
  }

  function uploadOne(file) {
    return new Promise((resolve, reject) => {
      const xhr = new XMLHttpRequest();
      xhr.open('POST', apiUrl);
      xhr.responseType = 'json';
      xhr.setRequestHeader('X-CSRF-Token', csrf);
      xhr.setRequestHeader('Accept', 'application/json');

      xhr.onload = () => {
        const data = xhr.response;
        if (xhr.status >= 200 && xhr.status < 300 && data && data.ok) {
          resolve(data);
        } else {
          reject(new Error((data && data.error) || 'Błąd uploadu'));
        }
      };
      xhr.onerror = () => reject(new Error('Błąd sieci'));

      const form = new FormData();
      form.append('action', 'upload');
      form.append('csrf', csrf);
      form.append('file', file, file.name);

      xhr.send(form);
    });
  }

  async function refresh() {
    try {
      refreshBtn && (refreshBtn.disabled = true);
      const files = await apiGetList();
      allFiles = files;
      render(allFiles);
    } catch (e) {
      await uiAlertError(e.message || String(e));
    } finally {
      refreshBtn && (refreshBtn.disabled = false);
    }
  }

  grid.addEventListener('click', async (ev) => {
    const btn = ev.target?.closest('button[data-action]');
    if (!btn) return;

    const action = btn.getAttribute('data-action');
    const name = btn.getAttribute('data-name');
    if (!action || !name) return;

    if (action === 'delete') {
      if (!(await uiConfirmDelete(name))) return;
      try {
        btn.disabled = true;
        await apiPost('delete', { name });
        await refresh();
      } catch (e) {
        await uiAlertError(e.message || String(e));
      } finally {
        btn.disabled = false;
      }
      return;
    }

    if (action === 'rename') {
      renameTarget = name;
      if (renameError) renameError.classList.add('d-none');
      if (renameInput) renameInput.value = baseOf(name);
      renameModal && renameModal.show();
      return;
    }
  });

  grid.addEventListener('change', async (ev) => {
    const sel = ev.target?.closest('select[data-action="category"]');
    if (!sel) return;
    const name = sel.getAttribute('data-name');
    const category = sel.value;
    if (!name || !category) return;

    try {
      sel.disabled = true;
      await apiPost('set_category', { name, category });
      await refresh();
    } catch (e) {
      await uiAlertError(e.message || String(e));
      sel.disabled = false;
    }
  });

  renameSaveBtn?.addEventListener('click', async () => {
    if (!renameTarget) return;
    const newBase = (renameInput?.value || '').trim();
    if (!newBase) return;

    try {
      renameSaveBtn.disabled = true;
      await apiPost('rename', { old: renameTarget, new_base: newBase });
      renameModal && renameModal.hide();
      renameTarget = null;
      await refresh();
    } catch (e) {
      if (renameError) {
        renameError.textContent = e.message || String(e);
        renameError.classList.remove('d-none');
      } else {
        alert(e.message || String(e));
      }
    } finally {
      renameSaveBtn.disabled = false;
    }
  });

  refreshBtn?.addEventListener('click', refresh);
  searchInput?.addEventListener('input', () => render(allFiles));

  uploadBtn?.addEventListener('click', async () => {
    const files = Array.from(uploadInput?.files || []);
    if (!files.length) return;

    uploadBtn.disabled = true;
    setUploadStatus(`Upload: 0/${files.length}`);

    try {
      let done = 0;
      for (const f of files) {
        await uploadOne(f);
        done++;
        setUploadStatus(`Upload: ${done}/${files.length}`);
      }
      uploadInput.value = '';
      setUploadStatus('Gotowe.');
      await refresh();
    } catch (e) {
      setUploadStatus('');
      await uiAlertError(e.message || String(e));
    } finally {
      uploadBtn.disabled = false;
      setTimeout(() => setUploadStatus(''), 2500);
    }
  });

  refresh();
})();
