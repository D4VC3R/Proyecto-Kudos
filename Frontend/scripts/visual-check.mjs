/**
 * Verificación visual automática de la SPA con Playwright: para cada ruta, ancho y tema abre la página, hace una
 * captura a página completa y comprueba errores de JS y de consola, desbordamiento horizontal, número de `h1`,
 * contraste del texto (WCAG: 4,5:1 y 3:1 en texto grande) y el anillo de foco de los 15 primeros Tab (≥ 3:1).
 * No se lanza a mano: `scripts/visual-check.sh` lo ejecuta en la imagen de Playwright para no instalar nada en el host.
 *
 * Argumentos (`--k v` o `--k=v`):
 *   --routes   obligatorio. Lista separada por comas, o un `.json` con un array de rutas (`"/x"` o
 *              `{ "path": "/x", "auth": true, "name": "opcional", "click": "selector", "mocks": [...], "clock": "…" }`).
 *              `click`: selector (o array de selectores, que se pulsan en orden) que se pulsa tras cargar
 *              (p. ej. una pestaña o un modal); el Tab empieza ahí.
 *              `mocks`: array de `{ "url": "**\/api/x", "method": "POST", "status": 200, "json": {…} }`; responde la
 *              petición sin llegar al backend (`method` y `status` son opcionales; sin `method` vale cualquiera).
 *              `clock`: fecha ISO que fija `Date` (los temporizadores siguen corriendo).
 *   --base     URL del frontend (por defecto http://localhost:5174).
 *   --widths   anchos en px (por defecto 360,768,1440). --height: alto del viewport (por defecto 900).
 *   --themes   `current` (por defecto, no toca nada), `all` (4 paletas × claro/oscuro) o una lista `azulejo-dark,…`.
 *   --out      carpeta de salida (por defecto ./visual-check-out): capturas, report.json y storageState.json.
 *   --auth     (bandera) las rutas de la lista van con sesión.
 *
 * `report.json` incluye, por fila, `focus.order`: los elementos que recorre el Tab, en orden.
 *
 * La sesión usa KUDOS_VISUAL_EMAIL (por defecto admin@kudos.com) y KUDOS_VISUAL_PASSWORD.
 *
 * Códigos de salida: 0 = todo bien, 1 = alguna comprobación falla, 2 = error de la herramienta (argumentos, login
 * o servidor caído).
 */
import { mkdir, readFile, stat, writeFile } from 'node:fs/promises';
import { join } from 'node:path';
import { chromium } from 'playwright';

const PALETTES = ['azulejo', 'original', 'taquilla', 'videoclub'];
const MODES = ['light', 'dark'];
const ALL_THEMES = PALETTES.flatMap((palette) => MODES.map((mode) => `${palette}-${mode}`));

class ToolError extends Error {}

const parseArgs = (argv) => {
  const args = {};
  for (let i = 0; i < argv.length; i += 1) {
    const arg = argv[i];
    if (!arg.startsWith('--')) throw new ToolError(`Argumento inesperado: ${arg}`);
    const eq = arg.indexOf('=');
    if (eq !== -1) {
      args[arg.slice(2, eq)] = arg.slice(eq + 1);
    } else if (arg === '--auth') {
      args.auth = true;
    } else {
      const next = argv[i + 1];
      if (next === undefined || next.startsWith('--')) throw new ToolError(`Falta el valor de ${arg}`);
      args[arg.slice(2)] = next;
      i += 1;
    }
  }
  return args;
};

const parseNumberList = (value, name) => {
  const list = String(value).split(',').map((n) => Number(n.trim()));
  if (!list.length || list.some((n) => !Number.isFinite(n) || n <= 0)) {
    throw new ToolError(`--${name} no es válido: «${value}» (lista de números separados por comas)`);
  }
  return list;
};

const parseThemes = (value) => {
  if (value === 'current') return ['current'];
  if (value === 'all') return ALL_THEMES;
  const list = String(value).split(',').map((t) => t.trim()).filter(Boolean);
  const invalid = list.filter((t) => !ALL_THEMES.includes(t));
  if (!list.length || invalid.length) {
    throw new ToolError(
      `--themes no es válido: «${invalid.join(', ') || value}». Usa current, all o una lista de ${ALL_THEMES.join(', ')}`,
    );
  }
  return list;
};

const loadRoutes = async (value, authDefault) => {
  if (!value || value === true) throw new ToolError('Falta --routes (lista separada por comas o archivo .json)');
  let items;
  const isJsonFile = value.endsWith('.json') && (await stat(value).then((s) => s.isFile()).catch(() => false));
  if (isJsonFile) {
    let parsed;
    try {
      parsed = JSON.parse(await readFile(value, 'utf8'));
    } catch (e) {
      throw new ToolError(`No se pudo leer ${value}: ${e.message}`);
    }
    if (!Array.isArray(parsed)) throw new ToolError(`${value} debe ser un array de rutas`);
    items = parsed;
  } else {
    items = value.split(',').map((r) => r.trim()).filter(Boolean);
  }
  const routes = items.map((item) => {
    const route = typeof item === 'string' ? { path: item } : item;
    if (!route || typeof route.path !== 'string' || !route.path) throw new ToolError('Ruta no válida en --routes');
    const path = route.path.startsWith('/') ? route.path : `/${route.path}`;
    const click = route.click ? [].concat(route.click) : [];
    if (click.some((selector) => typeof selector !== 'string' || !selector)) {
      throw new ToolError(`«click» de ${path} debe ser un selector o un array de selectores (cadenas)`);
    }
    const mocks = route.mocks ?? [];
    if (!Array.isArray(mocks) || mocks.some((mock) => !mock || typeof mock.url !== 'string' || !mock.url)) {
      throw new ToolError(`«mocks» de ${path} debe ser un array de objetos con «url»`);
    }
    if (route.clock !== undefined && Number.isNaN(new Date(route.clock).getTime())) {
      throw new ToolError(`«clock» de ${path} no es una fecha válida: «${route.clock}»`);
    }
    return { path, auth: route.auth ?? authDefault, name: route.name || slugify(path), click, mocks, clock: route.clock ?? null };
  });
  if (!routes.length) throw new ToolError('--routes no tiene ninguna ruta');
  return routes;
};

const slugify = (path) => {
  if (path === '/') return 'home';
  const slug = path.replace(/^\//, '').toLowerCase().replace(/[^a-z0-9]/g, '-').replace(/-+/g, '-');
  return slug || 'home';
};

// Todo lo que sigue a `page.evaluate` se ejecuta en el navegador: no puede usar nada del ámbito de Node.

const checkImages = async () => {
  const imgs = [...document.querySelectorAll('img')];
  imgs.forEach((img) => {
    if (img.loading === 'lazy') img.loading = 'eager';
  });
  const decoded = Promise.all(imgs.map((img) => img.decode().catch(() => {})));
  await Promise.race([decoded, new Promise((resolve) => setTimeout(resolve, 5000))]);
};

const checkLayout = () => {
  const describe = (el) => {
    const classes = (el.getAttribute('class') || '').split(/\s+/).filter(Boolean).slice(0, 3);
    return `${el.tagName.toLowerCase()}${el.id ? `#${el.id}` : ''}${classes.map((c) => `.${c}`).join('')}`;
  };
  const root = document.documentElement;
  const overflow = root.scrollWidth > root.clientWidth + 1;
  const offenders = [];
  if (overflow) {
    for (const el of document.body.querySelectorAll('*')) {
      const rect = el.getBoundingClientRect();
      if (rect.width > 0 && rect.right > root.clientWidth + 1) {
        offenders.push(describe(el));
        if (offenders.length >= 5) break;
      }
    }
  }
  const h1Count = [...document.querySelectorAll('h1')].filter((h) => h.checkVisibility()).length;
  return { overflow, offenders, h1Count, scrollWidth: root.scrollWidth, clientWidth: root.clientWidth };
};

const checkContrast = () => {
  const canvas = document.createElement('canvas');
  canvas.width = 1;
  canvas.height = 1;
  const ctx = canvas.getContext('2d', { willReadFrequently: true });
  const colorCache = new Map();
  // Cualquier sintaxis de CSS (rgb, oklch, color()) se normaliza pasándola por un canvas.
  const parse = (color) => {
    if (colorCache.has(color)) return colorCache.get(color);
    ctx.clearRect(0, 0, 1, 1);
    ctx.fillStyle = '#000';
    ctx.fillStyle = color;
    ctx.fillRect(0, 0, 1, 1);
    const [r, g, b, a] = ctx.getImageData(0, 0, 1, 1).data;
    const parsed = { r, g, b, a: a / 255 };
    colorCache.set(color, parsed);
    return parsed;
  };
  const over = (top, base) => ({
    r: top.r * top.a + base.r * (1 - top.a),
    g: top.g * top.a + base.g * (1 - top.a),
    b: top.b * top.a + base.b * (1 - top.a),
    a: 1,
  });
  const luminance = ({ r, g, b }) => {
    const [R, G, B] = [r, g, b].map((v) => {
      const c = v / 255;
      return c <= 0.04045 ? c / 12.92 : ((c + 0.055) / 1.055) ** 2.4;
    });
    return 0.2126 * R + 0.7152 * G + 0.0722 * B;
  };
  const ratio = (a, b) => {
    const [l1, l2] = [luminance(a), luminance(b)].sort((x, y) => y - x);
    return Math.round(((l1 + 0.05) / (l2 + 0.05)) * 100) / 100;
  };
  const fmt = ({ r, g, b }) => `rgb(${Math.round(r)}, ${Math.round(g)}, ${Math.round(b)})`;
  const describe = (el) => {
    const classes = (el.getAttribute('class') || '').split(/\s+/).filter(Boolean).slice(0, 3);
    return `${el.tagName.toLowerCase()}${el.id ? `#${el.id}` : ''}${classes.map((c) => `.${c}`).join('')}`;
  };
  // Sube por los ancestros acumulando fondos hasta el primero opaco. Un degradado o una imagen no se pueden medir.
  const effectiveBackground = (el) => {
    const layers = [];
    let base = { r: 255, g: 255, b: 255, a: 1 };
    for (let node = el; node; node = node.parentElement) {
      const cs = getComputedStyle(node);
      if (cs.backgroundImage !== 'none') return null;
      const bg = parse(cs.backgroundColor);
      if (bg.a > 0) layers.push(bg);
      if (bg.a >= 1) break;
    }
    if (layers.length && layers[layers.length - 1].a >= 1) base = layers.pop();
    return layers.reduceRight((acc, layer) => over(layer, acc), base);
  };

  // Una imagen, un degradado o un fondo que no es de un ancestro (p. ej. una capa absoluta detrás del texto) hace
  // que el fondo real no sea el de la cadena de ancestros: ese texto no se puede medir.
  const hasLayerBehind = (el, rect) => {
    const stack = document.elementsFromPoint(rect.left + rect.width / 2, rect.top + rect.height / 2);
    const own = stack.findIndex((n) => n === el || el.contains(n));
    // Fuera del viewport no hay nada que mirar: no se puede medir.
    if (!stack.length) return true;
    // Si el propio elemento no aparece, el texto está recortado o tapado (o es pointer-events: none): no se puede medir.
    if (own === -1) return true;
    return stack.slice(own + 1).some((n) => {
      if (n.contains(el)) return false;
      if (['IMG', 'VIDEO', 'CANVAS', 'PICTURE'].includes(n.tagName)) return true;
      const cs = getComputedStyle(n);
      return cs.backgroundImage !== 'none' || parse(cs.backgroundColor).a > 0;
    });
  };

  const failures = new Map();
  let checked = 0;
  let skipped = 0;
  const walker = document.createTreeWalker(document.body, NodeFilter.SHOW_TEXT);
  const range = document.createRange();
  for (let node = walker.nextNode(); node; node = walker.nextNode()) {
    const text = node.textContent.replace(/\s+/g, ' ').trim();
    const el = node.parentElement;
    if (!text || !el) continue;
    if (!el.checkVisibility({ opacityProperty: true, visibilityProperty: true })) continue;
    if (el.closest(':disabled, [aria-disabled="true"], script, style, noscript')) continue;
    // Los textos `sr-only` miden 1 px pero su contenido desborda: se descartan por el tamaño del elemento.
    const box = el.getBoundingClientRect();
    if (box.width <= 1 || box.height <= 1) continue;
    range.selectNodeContents(node);
    let rect = [...range.getClientRects()].find((r) => r.width > 1 && r.height > 1);
    if (!rect) continue;
    // `elementsFromPoint` solo ve lo que está en el viewport (también el de los contenedores con scroll horizontal).
    if (rect.top < 0 || rect.bottom > window.innerHeight || rect.left < 0 || rect.right > window.innerWidth) {
      el.scrollIntoView({ block: 'center', inline: 'center', behavior: 'instant' });
      rect = [...range.getClientRects()].find((r) => r.width > 1 && r.height > 1) || rect;
    }

    const bg = hasLayerBehind(el, rect) ? null : effectiveBackground(el);
    if (!bg) {
      skipped += 1;
      continue;
    }
    const cs = getComputedStyle(el);
    const fg = over(parse(cs.color), bg);
    const fontSize = parseFloat(cs.fontSize);
    const fontWeight = parseInt(cs.fontWeight, 10) || 400;
    const large = fontSize >= 24 || (fontSize >= 18.66 && fontWeight >= 700);
    const required = large ? 3 : 4.5;
    const value = ratio(fg, bg);
    checked += 1;
    if (value >= required) continue;
    const key = `${fmt(fg)}|${fmt(bg)}|${text}`;
    if (!failures.has(key)) {
      failures.set(key, {
        text: text.slice(0, 40),
        element: describe(el),
        fg: fmt(fg),
        bg: fmt(bg),
        ratio: value,
        required,
        fontSize,
        fontWeight,
      });
    }
  }
  window.scrollTo({ top: 0, behavior: 'instant' });
  return { checked, skipped, failedCount: failures.size, failures: [...failures.values()].slice(0, 50) };
};

// Un Tab: lee el estado de foco del elemento activo y lo deja enfocado para que el siguiente Tab siga desde ahí.
const inspectFocus = (isFirst) => {
  const el = document.activeElement;
  if (!el || el === document.body || el === document.documentElement) return { stop: true };
  if (!isFirst && el === window.__firstFocused) return { stop: true };
  if (isFirst) window.__firstFocused = el;

  const read = () => {
    const cs = getComputedStyle(el);
    return {
      outlineStyle: cs.outlineStyle,
      outlineWidth: cs.outlineWidth,
      outlineColor: cs.outlineColor,
      boxShadow: cs.boxShadow,
    };
  };
  const withFocus = read();
  el.blur();
  const without = read();
  el.focus();

  const canvas = document.createElement('canvas');
  canvas.width = 1;
  canvas.height = 1;
  const ctx = canvas.getContext('2d', { willReadFrequently: true });
  const parse = (color) => {
    ctx.clearRect(0, 0, 1, 1);
    ctx.fillStyle = '#000';
    ctx.fillStyle = color;
    ctx.fillRect(0, 0, 1, 1);
    const [r, g, b, a] = ctx.getImageData(0, 0, 1, 1).data;
    return { r, g, b, a: a / 255 };
  };
  const over = (top, base) => ({
    r: top.r * top.a + base.r * (1 - top.a),
    g: top.g * top.a + base.g * (1 - top.a),
    b: top.b * top.a + base.b * (1 - top.a),
    a: 1,
  });
  const luminance = ({ r, g, b }) => {
    const [R, G, B] = [r, g, b].map((v) => {
      const c = v / 255;
      return c <= 0.04045 ? c / 12.92 : ((c + 0.055) / 1.055) ** 2.4;
    });
    return 0.2126 * R + 0.7152 * G + 0.0722 * B;
  };
  const ratio = (a, b) => {
    const [l1, l2] = [luminance(a), luminance(b)].sort((x, y) => y - x);
    return Math.round(((l1 + 0.05) / (l2 + 0.05)) * 100) / 100;
  };
  const background = (start) => {
    const layers = [];
    let base = { r: 255, g: 255, b: 255, a: 1 };
    for (let node = start; node; node = node.parentElement) {
      const bg = parse(getComputedStyle(node).backgroundColor);
      if (bg.a > 0) layers.push(bg);
      if (bg.a >= 1) break;
    }
    if (layers.length && layers[layers.length - 1].a >= 1) base = layers.pop();
    return layers.reduceRight((acc, layer) => over(layer, acc), base);
  };

  const classes = (el.getAttribute('class') || '').split(/\s+/).filter(Boolean).slice(0, 3);
  const desc = `${el.tagName.toLowerCase()}${el.id ? `#${el.id}` : ''}${classes.map((c) => `.${c}`).join('')}`;
  const label = (el.getAttribute('aria-label') || el.textContent || '').trim().replace(/\s+/g, ' ').slice(0, 40);
  const outlineVisible =
    withFocus.outlineStyle !== 'none' &&
    parseFloat(withFocus.outlineWidth) > 0 &&
    (withFocus.outlineStyle !== without.outlineStyle ||
      withFocus.outlineWidth !== without.outlineWidth ||
      withFocus.outlineColor !== without.outlineColor);
  const shadowChanged = withFocus.boxShadow !== 'none' && withFocus.boxShadow !== without.boxShadow;
  if (!outlineVisible && !shadowChanged) return { stop: false, desc, label, indicator: false };

  const bg = background(el.parentElement || el);
  const colors = [];
  if (outlineVisible) colors.push(withFocus.outlineColor);
  if (shadowChanged) colors.push(...(withFocus.boxShadow.match(/(rgba?|hsla?|oklch|oklab|lab|lch|color)\([^)]*\)/g) || []));
  const ratios = colors.map((c) => ratio(over(parse(c), bg), bg));
  return { stop: false, desc, label, indicator: true, ratio: ratios.length ? Math.max(...ratios) : 0 };
};

const run = async (args) => {
  const base = String(args.base || 'http://localhost:5174').replace(/\/+$/, '');
  const widths = parseNumberList(args.widths || '360,768,1440', 'widths');
  const height = parseNumberList(args.height || '900', 'height')[0];
  const themes = parseThemes(args.themes || 'current');
  const out = String(args.out || './visual-check-out');
  const routes = await loadRoutes(args.routes, Boolean(args.auth));
  await mkdir(out, { recursive: true });

  const needsAuth = routes.some((r) => r.auth);
  const email = process.env.KUDOS_VISUAL_EMAIL || 'admin@kudos.com';
  const password = process.env.KUDOS_VISUAL_PASSWORD;
  if (needsAuth && !password) {
    throw new ToolError('Falta KUDOS_VISUAL_PASSWORD (visual-check.sh la lee de SEED_ADMIN_PASSWORD en Backend/.env)');
  }

  const storagePath = join(out, 'storageState.json');
  const startedAt = new Date().toISOString();
  const results = [];
  let browser;
  const notRunning = () => new ToolError(`No responde ${base}: levanta el frontend (docker compose up en Frontend/)`);

  try {
    browser = await chromium.launch({ channel: 'chromium' });

    if (needsAuth) {
      const context = await browser.newContext({ viewport: { width: 1440, height }, reducedMotion: 'reduce' });
      try {
        const page = await context.newPage();
        try {
          await page.goto(`${base}/login`, { waitUntil: 'networkidle', timeout: 30000 });
        } catch (e) {
          if (String(e.message).includes('ERR_CONNECTION_REFUSED')) throw notRunning();
          throw new ToolError(`No se pudo iniciar sesión con ${email}`);
        }
        try {
          await page.fill('input[name="email"]', email);
          await page.fill('input[name="password"]', password);
          await page.click('button[type="submit"]');
          await page.waitForURL((u) => !u.pathname.startsWith('/login'), { timeout: 20000 });
        } catch {
          throw new ToolError(`No se pudo iniciar sesión con ${email}`);
        }
        await context.storageState({ path: storagePath });
      } finally {
        await context.close();
      }
    }

    let firstGoto = true;
    for (const route of routes) {
      for (const width of widths) {
        for (const theme of themes) {
          const colorScheme = theme === 'current' ? undefined : theme.split('-')[1];
          const context = await browser.newContext({
            viewport: { width, height },
            reducedMotion: 'reduce',
            colorScheme,
            deviceScaleFactor: 1,
            storageState: route.auth ? storagePath : undefined,
          });
          if (theme !== 'current') {
            const [palette, mode] = theme.split('-');
            // Es la clave que lee el script anti-parpadeo de index.html, que se ejecuta antes del primer pintado.
            await context.addInitScript(
              ({ palette, mode }) => {
                try {
                  localStorage.setItem('kudos.theme', JSON.stringify({ palette, mode }));
                } catch {}
              },
              { palette, mode },
            );
          }
          const row = {
            route: route.path,
            name: route.name,
            width,
            theme,
            auth: Boolean(route.auth),
            click: route.click,
            error: null,
            jsErrors: [],
            consoleErrors: [],
            overflow: false,
            overflowElements: [],
            h1Count: 0,
            contrast: { checked: 0, skipped: 0, failedCount: 0, failures: [] },
            focus: { steps: 0, order: [], failures: [] },
            screenshot: null,
            ok: false,
          };
          try {
            const page = await context.newPage();
            page.on('pageerror', (e) => row.jsErrors.push(e.message));
            page.on('console', (m) => {
              if (m.type() === 'error') row.consoleErrors.push(m.text());
            });

            for (const mock of route.mocks) {
              await page.route(mock.url, (request) => {
                if (mock.method && mock.method.toUpperCase() !== request.request().method()) return request.fallback();
                // La API está en otro origen: sin estas cabeceras el navegador rechazaría la respuesta simulada.
                return request.fulfill({
                  status: mock.status ?? 200,
                  json: mock.json ?? {},
                  headers: {
                    'access-control-allow-origin': request.request().headers().origin || '*',
                    'access-control-allow-credentials': 'true',
                  },
                });
              });
            }
            if (route.clock) await page.clock.setFixedTime(new Date(route.clock));

            try {
              await page.goto(base + route.path, { waitUntil: 'networkidle', timeout: 30000 });
            } catch (e) {
              if (firstGoto && String(e.message).includes('ERR_CONNECTION_REFUSED')) throw notRunning();
              row.error = String(e.message).split('\n')[0];
            }
            firstGoto = false;

            for (const selector of row.error ? [] : route.click) {
              try {
                await page.locator(selector).first().click({ timeout: 10000 });
                await page.waitForLoadState('networkidle');
              } catch (e) {
                row.error = `No se pudo pulsar «${selector}»: ${String(e.message).split('\n')[0]}`;
                break;
              }
            }

            if (!row.error) {
              await page.evaluate(checkImages);
              await page.waitForTimeout(300);

              const layout = await page.evaluate(checkLayout);
              row.overflow = layout.overflow;
              row.overflowElements = layout.offenders;
              row.h1Count = layout.h1Count;
              const file = `${route.name}-${width}-${theme}${route.auth ? '-sesion' : ''}.png`;
              await page.screenshot({ path: join(out, file), fullPage: true, animations: 'disabled' });
              row.screenshot = file;
              // El contraste desplaza el scroll para medir lo que hay detrás del texto, así que va después de la captura.
              row.contrast = await page.evaluate(checkContrast);

              // Sin transiciones, el estilo con foco se lee ya terminado y no a mitad de la animación.
              await page.addStyleTag({
                content: '*, *::before, *::after { transition: none !important; animation: none !important; }',
              });
              for (let step = 0; step < 15; step += 1) {
                await page.keyboard.press('Tab');
                const info = await page.evaluate(inspectFocus, step === 0);
                if (info.stop) break;
                row.focus.steps += 1;
                row.focus.order.push(info.label ? `${info.desc} «${info.label}»` : info.desc);
                if (!info.indicator) row.focus.failures.push(`sin indicador de foco en ${info.desc}`);
                else if (info.ratio < 3) row.focus.failures.push(`foco con contraste ${info.ratio}:1 (mín 3) en ${info.desc}`);
              }
            }
          } finally {
            await context.close();
          }
          row.ok =
            !row.error &&
            !row.jsErrors.length &&
            !row.consoleErrors.length &&
            !row.overflow &&
            row.h1Count === 1 &&
            !row.contrast.failedCount &&
            !row.focus.failures.length;
          results.push(row);
        }
      }
    }
  } finally {
    if (browser) await browser.close();
  }

  const summary = { total: results.length, failed: results.filter((r) => !r.ok).length };
  await writeFile(join(out, 'report.json'), JSON.stringify({ base, startedAt, widths, themes, results, summary }, null, 2));

  console.table(
    results.map((r) => ({
      ruta: r.route,
      ancho: r.width,
      tema: r.theme,
      sesion: r.auth ? 'sí' : 'no',
      js: r.jsErrors.length,
      consola: r.consoleErrors.length,
      desborda: r.overflow ? 'sí' : 'no',
      h1: r.h1Count,
      contraste: r.contrast.failedCount,
      foco: r.focus.failures.length,
      estado: r.ok ? 'OK' : 'FALLA',
    })),
  );

  for (const r of results.filter((x) => !x.ok)) {
    console.log(`\n${r.route} · ${r.width}px · ${r.theme}${r.auth ? ' · con sesión' : ''}`);
    if (r.error) console.log(`  error de carga: ${r.error}`);
    for (const f of r.contrast.failures.slice(0, 10)) {
      console.log(`  contraste ${f.ratio}:1 (mín ${f.required}) «${f.text}» ${f.fg} sobre ${f.bg} [${f.element}]`);
    }
    if (r.contrast.failedCount > 10) console.log(`  … y ${r.contrast.failedCount - 10} fallos de contraste más (ver report.json)`);
    r.jsErrors.forEach((m) => console.log(`  error de JS: ${m}`));
    r.consoleErrors.forEach((m) => console.log(`  error de consola: ${m}`));
    if (r.overflow) console.log(`  desbordamiento horizontal: ${r.overflowElements.join(', ') || 'sin elemento identificado'}`);
    if (r.h1Count !== 1 && !r.error) console.log(`  h1 visibles: ${r.h1Count} (debe haber 1)`);
    r.focus.failures.forEach((m) => console.log(`  ${m}`));
  }
  console.log(`\n${summary.total} comprobaciones, ${summary.failed} con fallos. Salida en ${out}`);
  return summary.failed ? 1 : 0;
};

let code = 2;
try {
  code = await run(parseArgs(process.argv.slice(2)));
} catch (e) {
  console.error(e instanceof ToolError ? e.message : `Error inesperado: ${e.stack || e.message}`);
}
process.exit(code);
