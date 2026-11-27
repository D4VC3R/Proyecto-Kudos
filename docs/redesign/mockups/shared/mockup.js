// Panel de la maqueta: cambia de pantalla, de tema y de ancho sin tocar el diseño de cada dirección.
// Cada maqueta marca sus pantallas con [data-screen="home|ficha|votar"] y puede navegar con [data-go="ficha"].
// Parámetros de la URL: ?screen=home&theme=light|dark&palette=…&embed=1 (embed oculta el panel; lo usa la vista móvil).
// Opcional en <html>: data-palettes="clave:Nombre,…" añade un selector de paleta; data-motion activa las
// transiciones entre pantallas y la del cambio de tema (View Transitions; sin soporte, el cambio es instantáneo).
// Si cambias este archivo o data.js, sube el ?v= de sus <script> en las maquetas: si no, el navegador puede seguir usando la versión guardada.
(function () {
  const params = new URLSearchParams(location.search);
  const embed = params.has('embed');
  const root = document.documentElement;
  const prefersDark = window.matchMedia('(prefers-color-scheme: dark)').matches;
  const reduceMotion = window.matchMedia('(prefers-reduced-motion: reduce)');
  const palettes = (root.dataset.palettes || '').split(',').filter(Boolean).map((p) => p.split(':'));
  const state = {
    screen: params.get('screen') || 'home',
    theme: params.get('theme') || (prefersDark ? 'dark' : 'light'),
    palette: params.get('palette') || root.dataset.palette || '',
    mobile: false,
  };

  // Aplicar el tema y la paleta antes de pintar, para que no parpadee.
  root.classList.toggle('dark', state.theme === 'dark');
  if (state.palette) root.dataset.palette = state.palette;

  // Envuelve un cambio en una transición de vista si la página la pide y el navegador la admite.
  function transition(kind, update, origin) {
    if (!root.hasAttribute('data-motion') || !document.startViewTransition || reduceMotion.matches || state.mobile) { update(); return; }
    root.dataset.vt = kind;
    if (origin) { root.style.setProperty('--vt-x', `${origin.x}px`); root.style.setProperty('--vt-y', `${origin.y}px`); }
    document.startViewTransition(update).finished.finally(() => { delete root.dataset.vt; });
  }
  const centerOf = (el) => { const r = el.getBoundingClientRect(); return { x: r.left + r.width / 2, y: r.top + r.height / 2 }; };

  const screens = [
    ['home', 'Portada'],
    ['ficha', 'Ficha'],
    ['votar', 'Votar'],
  ];

  function syncUrl() {
    const p = new URLSearchParams(location.search);
    p.set('screen', state.screen);
    p.set('theme', state.theme);
    if (palettes.length) p.set('palette', state.palette);
    history.replaceState(null, '', `${location.pathname}?${p}`);
  }

  function showScreen() {
    document.querySelectorAll('[data-screen]').forEach((el) => {
      el.hidden = el.dataset.screen !== state.screen;
    });
    window.scrollTo(0, 0);
    document.dispatchEvent(new CustomEvent('mockup:screen', { detail: state.screen }));
  }

  let shownScreen = null;
  function render() {
    root.classList.toggle('dark', state.theme === 'dark');
    if (state.palette) root.dataset.palette = state.palette;
    if (shownScreen !== state.screen) { shownScreen = state.screen; showScreen(); }
    syncUrl();
    renderPanel();
    renderFrame();
  }

  const api = {
    go(screen) { if (screen === state.screen) return; transition('screen', () => { state.screen = screen; render(); }); },
    setTheme(theme, origin) { if (theme === state.theme) return; transition('theme', () => { state.theme = theme; render(); }, origin); },
    toggleTheme(origin) { api.setTheme(state.theme === 'dark' ? 'light' : 'dark', origin); },
    setPalette(palette) { transition('palette', () => { state.palette = palette; render(); }); },
    get theme() { return state.theme; },
    get screen() { return state.screen; },
  };
  window.Mockup = api;

  document.addEventListener('click', (e) => {
    const go = e.target.closest('[data-go]');
    if (go) { e.preventDefault(); api.go(go.dataset.go); }
    const tt = e.target.closest('[data-theme-toggle]');
    if (tt) { e.preventDefault(); api.toggleTheme(centerOf(tt)); }
  });

  // --- Panel (estilos propios con prefijo mk-, aislados del diseño) ---
  let panel;
  let frame;
  function renderPanel() {
    if (embed) return;
    if (!panel) {
      const style = document.createElement('style');
      style.textContent = `
        .mk-panel{view-transition-name:mk-panel;position:fixed;z-index:9999;right:12px;bottom:12px;display:flex;flex-wrap:wrap;gap:6px;align-items:center;max-width:calc(100vw - 24px);
          padding:8px;border-radius:10px;background:#111;color:#fff;font:500 12px/1.2 system-ui,sans-serif;box-shadow:0 6px 24px rgba(0,0,0,.35)}
        .mk-panel b{font-weight:700;margin:0 4px}
        .mk-panel .mk-g{display:flex;gap:2px;background:#2a2a2a;border-radius:7px;padding:2px}
        .mk-panel button{all:unset;cursor:pointer;padding:6px 8px;border-radius:5px;color:#ddd}
        .mk-panel button[aria-pressed=true]{background:#fff;color:#111}
        .mk-panel button:focus-visible{outline:2px solid #6cf;outline-offset:1px}
        .mk-panel a{color:#9cf;margin-left:4px}
        .mk-frame{position:fixed;inset:0;z-index:9998;background:#555;display:flex;justify-content:center;align-items:flex-start;padding:16px 0 72px;overflow:auto}
        .mk-frame iframe{width:390px;height:calc(100vh - 88px);border:0;border-radius:18px;background:#fff;box-shadow:0 10px 40px rgba(0,0,0,.4)}`;
      document.head.appendChild(style);
      panel = document.createElement('div');
      panel.className = 'mk-panel';
      panel.setAttribute('role', 'toolbar');
      panel.setAttribute('aria-label', 'Controles de la maqueta');
      document.body.appendChild(panel);
      panel.addEventListener('click', (e) => {
        const b = e.target.closest('button');
        if (!b) return;
        if (b.dataset.s) api.go(b.dataset.s);
        if (b.dataset.t) api.setTheme(b.dataset.t, centerOf(b));
        if (b.dataset.p) api.setPalette(b.dataset.p);
        if (b.dataset.m) { state.mobile = b.dataset.m === '1'; render(); }
      });
    }
    const btn = (attr, val, label, on) => `<button type="button" data-${attr}="${val}" aria-pressed="${on}">${label}</button>`;
    panel.innerHTML = `<b>${document.title.replace('Maqueta ', '')}</b>
      <span class="mk-g">${screens.map(([k, l]) => btn('s', k, l, state.screen === k)).join('')}</span>
      ${palettes.length ? `<span class="mk-g">${palettes.map(([k, l]) => btn('p', k, l, state.palette === k)).join('')}</span>` : ''}
      <span class="mk-g">${btn('t', 'light', 'Claro', state.theme === 'light')}${btn('t', 'dark', 'Oscuro', state.theme === 'dark')}</span>
      <span class="mk-g">${btn('m', '0', 'Escritorio', !state.mobile)}${btn('m', '1', 'Móvil', state.mobile)}</span>
      <a href="index.html">Todas</a>`;
  }

  function renderFrame() {
    if (embed) return;
    if (!state.mobile) { frame?.remove(); frame = null; return; }
    if (!frame) {
      frame = document.createElement('div');
      frame.className = 'mk-frame';
      document.body.appendChild(frame);
    }
    const src = `${location.pathname}?screen=${state.screen}&theme=${state.theme}${state.palette ? `&palette=${state.palette}` : ''}&embed=1`;
    frame.innerHTML = `<iframe title="Vista móvil (390 px)" src="${src}"></iframe>`;
  }

  window.addEventListener('DOMContentLoaded', render);
})();
