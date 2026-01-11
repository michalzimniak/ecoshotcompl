/* global GLightbox, bootstrap */

(function () {
  'use strict';

  function qs(sel, root) { return (root || document).querySelector(sel); }
  function qsa(sel, root) { return Array.from((root || document).querySelectorAll(sel)); }

  function initLightbox() {
    if (typeof GLightbox !== 'function') return;
    GLightbox({ selector: '.glightbox', touchNavigation: true, loop: true });
  }

  function initScrollAnimations() {
    const els = qsa('.animate-on-scroll');
    if (!('IntersectionObserver' in window)) {
      els.forEach(el => el.classList.add('is-visible', 'animate__animated', 'animate__fadeInUp'));
      return;
    }

    const io = new IntersectionObserver((entries) => {
      entries.forEach((entry) => {
        if (!entry.isIntersecting) return;
        const el = entry.target;
        const anim = el.getAttribute('data-animate') || 'animate__fadeInUp';
        el.classList.add('is-visible', 'animate__animated', anim);
        io.unobserve(el);
      });
    }, { threshold: 0.12 });

    els.forEach(el => io.observe(el));
  }

  function initSmoothNavClose() {
    const nav = qs('#mainNav');
    if (!nav) return;

    qsa('a.nav-link', nav).forEach((a) => {
      a.addEventListener('click', () => {
        const isShown = nav.classList.contains('show');
        if (!isShown) return;
        const bsCollapse = bootstrap.Collapse.getOrCreateInstance(nav);
        bsCollapse.hide();
      });
    });
  }

  function initPortfolioFilters() {
    const grid = qs('#portfolioGrid');
    if (!grid) return;

    const buttons = qsa('.btn-filter');
    const items = qsa('.portfolio-item', grid);

    function setActive(btn) {
      buttons.forEach(b => b.classList.remove('active'));
      btn.classList.add('active');
    }

    function applyFilter(filter) {
      items.forEach((item) => {
        const cat = item.getAttribute('data-category');
        const show = (filter === 'all') || (cat === filter);
        item.style.display = show ? '' : 'none';
      });
    }

    buttons.forEach((btn) => {
      btn.addEventListener('click', () => {
        const filter = btn.getAttribute('data-filter') || 'all';
        setActive(btn);
        applyFilter(filter);
      });
    });
  }

  function initOfferPrefill() {
    qsa('[data-prefill]').forEach((el) => {
      el.addEventListener('click', () => {
        const legacy = el.getAttribute('data-prefill');
        const serviceValue = el.getAttribute('data-prefill-service');
        const messageValue = el.getAttribute('data-prefill-message');

        const service = qs('#service');
        const message = qs('#message');

        if (service) {
          const wanted = (serviceValue || legacy || '').trim();
          if (wanted) {
            const opt = qsa('option', service).find(o => (o.textContent || '').trim().toLowerCase() === wanted.toLowerCase())
              || qsa('option', service).find(o => (o.textContent || '').trim().toLowerCase().includes(wanted.toLowerCase()));
            if (opt) {
              service.value = opt.textContent.trim();
            } else {
              service.value = 'Inne';
            }
          }
        }

        if (message && messageValue) {
          const cur = (message.value || '').trim();
          if (cur === '') {
            message.value = messageValue;
          } else if (!cur.toLowerCase().includes(messageValue.toLowerCase())) {
            message.value = cur + "\n\n" + messageValue;
          }
        }
      });
    });
  }

  function initContactForm() {
    const form = qs('#contactForm');
    if (!form) return;

    const submitBtn = qs('#contactSubmit');
    const alertBox = qs('#contactAlert');

    function setAlert(type, msg) {
      if (!alertBox) return;
      alertBox.innerHTML = `<div class="alert alert-${type} py-2 mb-0" role="alert">${msg}</div>`;
    }

    function setLoading(isLoading) {
      if (!submitBtn) return;
      submitBtn.classList.toggle('is-loading', isLoading);
      submitBtn.disabled = isLoading;
    }

    form.addEventListener('submit', async (e) => {
      e.preventDefault();
      setAlert('', '');

      form.classList.add('was-validated');
      if (!form.checkValidity()) {
        setAlert('danger', 'Sprawdź pola formularza i spróbuj ponownie.');
        return;
      }

      const data = new FormData(form);

      try {
        setLoading(true);
        const res = await fetch('/contact.php', { method: 'POST', body: data, headers: { 'Accept': 'application/json' } });
        const payload = await res.json().catch(() => ({}));

        if (!res.ok || !payload || payload.success !== true) {
          const msg = (payload && payload.message) ? payload.message : 'Nie udało się wysłać wiadomości. Spróbuj ponownie.';
          setAlert('danger', msg);
          setLoading(false);
          return;
        }

        setAlert('success', payload.message || 'Wiadomość wysłana! Odpowiem najszybciej, jak to możliwe.');
        form.reset();
        form.classList.remove('was-validated');
      } catch (err) {
        setAlert('danger', 'Błąd połączenia. Spróbuj ponownie za chwilę.');
      } finally {
        setLoading(false);
      }
    });
  }

  function initSpaRouter() {
    const titlePrefix = 'EcoShot Danuta Zimniak - ';

    function setDocumentTitle(routeTitle) {
      const t = (routeTitle || '').trim();
      if (!t) return;
      document.title = t.startsWith(titlePrefix) ? t : `${titlePrefix}${t}`;
    }

    const routes = {
      '/': { target: '#hero', title: 'EcoShot Danuta Zimniak - Fotografia Bydgoszcz | Sesja rodzinna i biznesowa' },
      // kompatybilność wsteczna (stare podstrony)
    //   '/sesje-rodzinne': { target: '#oferta-rodzinne', title: 'Sesje rodzinne | Oferta' },
    //   '/sesje-biznesowe': { target: '#oferta-biznesowe', title: 'Sesje biznesowe | Oferta' },
      '/oferta': { target: '#oferta', title: 'EcoShot Danuta Zimniak - Oferta' },
      '/cennik': { target: '#cennik', title: 'EcoShot Danuta Zimniak - Cennik' },
      '/portfolio': { target: '#portfolio', title: 'EcoShot Danuta Zimniak - Portfolio' },
      '/wspolpraca': { target: '#wspolpraca', title: 'EcoShot Danuta Zimniak - Współpraca' },
      '/kontakt': { target: '#kontakt', title: 'EcoShot Danuta Zimniak - Kontakt' }
    };

    function normalizePath(pathname) {
      if (!pathname) return '/';
      let p = pathname;
      if (!p.startsWith('/')) p = '/' + p;
      if (p.length > 1 && p.endsWith('/')) p = p.slice(0, -1);
      return p;
    }

    function setActiveNav(path) {
      const navRoot = qs('nav.navbar');
      const navLinks = navRoot ? qsa('[data-route]', navRoot) : [];
      navLinks.forEach((a) => {
        const r = a.getAttribute('data-route');
        const isActive = r && normalizePath(r) === path;
        if (a.classList.contains('nav-link')) {
          a.classList.toggle('active', isActive);
        }
        if (isActive) {
          a.setAttribute('aria-current', 'page');
        } else {
          a.removeAttribute('aria-current');
        }
      });
    }

    function scrollToTarget(selector) {
      const el = qs(selector);
      if (!el) return;
      el.scrollIntoView({ behavior: 'smooth', block: 'start' });
    }

    function applyRoute(path, replace) {
      const route = routes[path] || routes['/'];
      if (replace) {
        history.replaceState({ path }, '', path);
      } else {
        history.pushState({ path }, '', path);
      }

      setActiveNav(path);
      if (route && route.title) {
        setDocumentTitle(route.title);
      }

      // scroll after title/state update
      requestAnimationFrame(() => scrollToTarget(route.target));
    }

    // Intercept clicks
    qsa('[data-route]').forEach((a) => {
      a.addEventListener('click', (e) => {
        const raw = a.getAttribute('data-route');
        if (!raw) return;
        const path = normalizePath(raw);
        if (!routes[path]) return;
        e.preventDefault();
        applyRoute(path, false);
      });
    });

    window.addEventListener('popstate', () => {
      const path = normalizePath(window.location.pathname);
      const route = routes[path] || routes['/'];
      setActiveNav(path);
      if (route && route.title) setDocumentTitle(route.title);
      requestAnimationFrame(() => scrollToTarget(route.target));
    });

    // Initial route
    const initial = normalizePath(window.location.pathname);
    const initialRoute = routes[initial] ? initial : '/';
    if (!routes[initial]) {
      history.replaceState({ path: '/' }, '', '/');
    }
    setActiveNav(initialRoute);
    if (routes[initialRoute] && routes[initialRoute].title) setDocumentTitle(routes[initialRoute].title);
    // If someone enters via /oferta, scroll to that section
    if (initialRoute !== '/') {
      requestAnimationFrame(() => scrollToTarget(routes[initialRoute].target));
    }
  }

  document.addEventListener('DOMContentLoaded', () => {
    initLightbox();
    initScrollAnimations();
    initSmoothNavClose();
    initPortfolioFilters();
    initOfferPrefill();
    initContactForm();
    initSpaRouter();
  });
})();
