/**
 * Meta tags, Open Graph y JSON-LD de las fichas para los rastreadores (F2-10).
 * La usa `api/meta.js`; la carpeta `_lib` no se publica como función en Vercel.
 * La F3-09 añadirá `/listas/:slug` y `/u/:username`.
 */

/** Nombre del sitio en `og:site_name` y en el sufijo del título. */
export const SITE_NAME = 'Kudos';

/** Longitud máxima de la descripción (meta description y og:description). */
export const DESCRIPTION_MAX_LENGTH = 160;

/** Título por defecto: el mismo `<title>` que `index.html`. */
export const DEFAULT_TITLE = 'Proyecto Kudos';

/** Descripción genérica para cuando no hay datos de la ficha. */
export const DEFAULT_DESCRIPTION = 'Descubre, valora y guarda películas, series y mucho más en Kudos.';

/** Tiempo máximo de espera de la API (ms). */
export const API_TIMEOUT_MS = 3000;

/** Tiempo máximo de espera de la plantilla `index.html` (ms). */
export const TEMPLATE_TIMEOUT_MS = 2000;

/** Cabecera de caché de las respuestas correctas: el CDN de Vercel las guarda 10 min; el navegador, nada. */
export const CACHE_CONTROL_OK = 'public, max-age=0, s-maxage=600, stale-while-revalidate=3600';

/** Cabecera de caché de las respuestas que no deben guardarse. */
export const CACHE_CONTROL_NONE = 'no-store';

/** Tipo de schema.org según el slug de la categoría; cualquier otra categoría es CreativeWork. */
export const SCHEMA_TYPES = { peliculas: 'Movie', series: 'TVSeries', videojuegos: 'VideoGame', destinos: 'Place' };

/** og:type según el tipo de schema.org; el resto, website. */
export const OG_TYPES = { Movie: 'video.movie', TVSeries: 'video.tv_show' };

/** Plantilla mínima para cuando no se puede leer el `index.html` compilado. */
export const FALLBACK_TEMPLATE = `<!doctype html>
<html lang="es">
  <head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>${DEFAULT_TITLE}</title>
  </head>
  <body>
    <div id="root"></div>
  </body>
</html>
`;

const UUID_PATTERN = /^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/i;

/** Escapa un valor para usarlo en HTML o en un atributo entre comillas. */
export function escapeHtml(value) {
  return String(value ?? '')
    .replace(/&/g, '&amp;')
    .replace(/</g, '&lt;')
    .replace(/>/g, '&gt;')
    .replace(/"/g, '&quot;')
    .replace(/'/g, '&#39;');
}

/** Indica si el valor es un UUID (los IDs de los ítems lo son). */
export function isUuid(value) {
  return typeof value === 'string' && UUID_PATTERN.test(value);
}

/** Colapsa los espacios y recorta el texto sin partir palabras, añadiendo «…» si hace falta. */
export function truncateText(text, maxLength = DESCRIPTION_MAX_LENGTH) {
  const clean = String(text ?? '').replace(/\s+/g, ' ').trim();
  if (clean.length <= maxLength) return clean;

  const limit = maxLength - 1;
  const lastSpace = clean.lastIndexOf(' ', limit);
  const cut = lastSpace > 0 ? lastSpace : limit;
  const truncated = clean.slice(0, cut).trimEnd().replace(/[,.;:]+$/, '');
  return `${truncated}…`;
}

/** Primer valor de una cabecera (puede ser un array o una lista separada por comas). */
export function firstHeader(value) {
  const raw = Array.isArray(value) ? value[0] : value;
  if (raw === undefined || raw === null) return null;
  const first = String(raw).split(',')[0].trim();
  return first || null;
}

/** Origen público de la petición (se usa el de la petición, no VERCEL_URL, que puede estar protegido). */
export function getSiteOrigin(headers = {}) {
  const host = firstHeader(headers['x-forwarded-host']) ?? firstHeader(headers.host) ?? 'localhost';
  const isLocal = /^(localhost|127\.0\.0\.1|\[::1\])(:\d+)?$/.test(host);
  const proto = firstHeader(headers['x-forwarded-proto']) ?? (isLocal ? 'http' : 'https');
  return `${proto}://${host}`;
}

/** URL absoluta de un fichero del storage (como `getStorageUrl` del frontend). */
export function resolveStorageUrl(path, env = {}) {
  if (!path) return null;
  if (/^https?:\/\//i.test(path)) return path;

  let base = env.VITE_STORAGE_URL;
  if (!base && env.VITE_API_URL && /\/api\/?$/.test(env.VITE_API_URL)) {
    base = env.VITE_API_URL.replace(/\/api\/?$/, '/storage/');
  }
  if (!base || !/^https?:\/\//i.test(base)) return null;

  return `${base.replace(/\/+$/, '')}/${path.replace(/^\/+/, '')}`;
}

/** Imagen para OG: la variante `banner` de la primera imagen apaisada o, si no hay, de la primera con variantes. */
export function getItemImage(item, env = {}) {
  const images = Array.isArray(item?.images) ? item.images : [];
  const image = images.find((img) => img?.meta?.orientation === 'landscape' && img?.variants?.banner)
    ?? images.find((img) => img?.variants?.banner || img?.variants?.thumb);
  if (!image) return null;

  const url = resolveStorageUrl(image.variants.banner || image.variants.thumb, env);
  return url ? { url, alt: image.alt || null } : null;
}

/** Tipo de schema.org de una categoría. */
export function getSchemaType(categorySlug) {
  return SCHEMA_TYPES[categorySlug] ?? 'CreativeWork';
}

/** Serializa JSON-LD de forma que el texto nunca pueda cerrar el `<script>`. */
export function serializeJsonLd(data) {
  return JSON.stringify(data)
    .replace(/</g, '\\u003c')
    .replace(/>/g, '\\u003e')
    .replace(/&/g, '\\u0026')
    .replace(/\u2028/g, '\\u2028')
    .replace(/\u2029/g, '\\u2029');
}

/** Metadatos de una ficha de ítem. */
export function buildItemMeta(item, { origin, env = {} }) {
  const categorySlug = item.category?.slug ?? null;
  const schemaType = getSchemaType(categorySlug);
  const year = /^\d{4}/.test(item.released_at ?? '') && schemaType !== 'Place'
    ? item.released_at.slice(0, 4)
    : null;
  const ogTitle = year ? `${item.name} (${year})` : item.name;
  const title = `${ogTitle} · ${SITE_NAME}`;
  const description = truncateText(item.description)
    || `${item.name} en Kudos: mira su nota media y deja tu valoración.`;
  // URL canónica: si el slug de la petición no coincide, manda el de la categoría real.
  const url = categorySlug
    ? `${origin}/${encodeURIComponent(categorySlug)}/item/${encodeURIComponent(item.id)}`
    : `${origin}/`;
  const image = getItemImage(item, env);

  const jsonLd = {
    '@context': 'https://schema.org',
    '@type': schemaType,
    name: item.name,
    url,
    ...(truncateText(item.description) ? { description: truncateText(item.description) } : {}),
    ...(image ? { image: image.url } : {}),
    ...(item.original_title && item.original_title !== item.name ? { alternateName: item.original_title } : {}),
    ...(item.released_at && schemaType !== 'Place' ? { datePublished: item.released_at } : {}),
    ...(Number(item.vote_count) > 0 ? {
      aggregateRating: {
        '@type': 'AggregateRating',
        ratingValue: Math.round(Number(item.vote_avg) * 10) / 10,
        ratingCount: Number(item.vote_count),
        bestRating: 10,
        worstRating: 0,
      },
    } : {}),
  };

  return {
    title,
    ogTitle,
    description,
    url,
    image: image?.url ?? null,
    imageAlt: image?.alt ?? item.name,
    ogType: OG_TYPES[schemaType] ?? 'website',
    twitterCard: image ? 'summary_large_image' : 'summary',
    jsonLd,
  };
}

/** Metadatos genéricos del sitio. */
export function buildDefaultMeta({ origin }) {
  return {
    title: DEFAULT_TITLE,
    ogTitle: SITE_NAME,
    description: DEFAULT_DESCRIPTION,
    url: `${origin}/`,
    image: null,
    imageAlt: null,
    ogType: 'website',
    twitterCard: 'summary',
    jsonLd: null,
  };
}

/** Genera las etiquetas HTML (una cadena cada una) que se insertan en el `<head>`. */
export function renderMetaTags(meta) {
  const name = (key, content) => `<meta name="${key}" content="${escapeHtml(content)}">`;
  const property = (key, content) => `<meta property="${key}" content="${escapeHtml(content)}">`;

  const tags = [
    name('description', meta.description),
    `<link rel="canonical" href="${escapeHtml(meta.url)}">`,
    property('og:site_name', SITE_NAME),
    property('og:locale', 'es_ES'),
    property('og:type', meta.ogType),
    property('og:title', meta.ogTitle),
    property('og:description', meta.description),
    property('og:url', meta.url),
  ];

  if (meta.image) {
    tags.push(property('og:image', meta.image), property('og:image:alt', meta.imageAlt));
  }

  tags.push(
    name('twitter:card', meta.twitterCard),
    name('twitter:title', meta.ogTitle),
    name('twitter:description', meta.description),
  );

  if (meta.image) {
    tags.push(name('twitter:image', meta.image), name('twitter:image:alt', meta.imageAlt));
  }

  if (meta.jsonLd) {
    tags.push(`<script type="application/ld+json">${serializeJsonLd(meta.jsonLd)}</script>`);
  }

  return tags;
}

/** Inserta título y meta tags en la plantilla HTML, conservando el resto del `<head>`. */
export function injectMeta(template, meta) {
  const source = /<\/head>/i.test(template) ? template : FALLBACK_TEMPLATE;
  const titleTag = `<title>${escapeHtml(meta.title)}</title>`;
  const titlePattern = /<title>[\s\S]*?<\/title>/i;

  const tags = renderMetaTags(meta);
  let html = source;
  if (titlePattern.test(html)) {
    // Función de reemplazo: un `$` en los datos no debe interpretarse.
    html = html.replace(titlePattern, () => titleTag);
  } else {
    tags.unshift(titleTag);
  }

  return html.replace(
    /(\s*)<\/head>/i,
    (_, ws) => `\n${tags.map((tag) => `    ${tag}`).join('\n')}${ws}</head>`,
  );
}

/** Lee `categorySlug` e `itemId` de la query, de la URL o de la ruta original. */
export function parseItemParams(req) {
  const pick = (value) => (Array.isArray(value) ? value[0] : value);
  let categorySlug = pick(req.query?.categorySlug);
  let itemId = pick(req.query?.itemId);

  const url = new URL(req.url ?? '/', 'http://localhost');
  categorySlug = categorySlug || url.searchParams.get('categorySlug');
  itemId = itemId || url.searchParams.get('itemId');

  if (!itemId) {
    const match = url.pathname.match(/^\/([^/]+)\/item\/([^/]+)\/?$/);
    if (match) {
      try {
        categorySlug = categorySlug || decodeURIComponent(match[1]);
        itemId = decodeURIComponent(match[2]);
      } catch {
        // Ruta con codificación inválida: se ignora.
      }
    }
  }

  return { categorySlug: categorySlug || null, itemId: itemId || null };
}

/** Crea el manejador de la función; la plantilla se cachea en memoria mientras viva la instancia. */
export function createMetaHandler({
  fetchImpl = (...args) => globalThis.fetch(...args),
  env = process.env,
  apiTimeoutMs = API_TIMEOUT_MS,
  templateTimeoutMs = TEMPLATE_TIMEOUT_MS,
} = {}) {
  let cachedTemplate = null;

  async function loadTemplate(origin, requestHeaders) {
    if (cachedTemplate) return { html: cachedTemplate, fromOrigin: true };

    try {
      // Para atravesar la protección de los despliegues de Vercel se reenvían el bypass y la cookie.
      const headers = { Accept: 'text/html' };
      const bypass = firstHeader(requestHeaders['x-vercel-protection-bypass']);
      if (bypass) headers['x-vercel-protection-bypass'] = bypass;
      const cookie = requestHeaders.cookie;
      if (cookie) headers.cookie = Array.isArray(cookie) ? cookie.join('; ') : cookie;

      const response = await fetchImpl(`${origin}/index.html`, {
        headers,
        signal: AbortSignal.timeout(templateTimeoutMs),
      });
      if (response.ok) {
        const html = await response.text();
        if (/<\/head>/i.test(html) && html.includes('id="root"')) {
          cachedTemplate = html;
          return { html, fromOrigin: true };
        }
      }
    } catch {
      // Se usa la plantilla de reserva.
    }
    return { html: FALLBACK_TEMPLATE, fromOrigin: false };
  }

  async function fetchItem(itemId) {
    const apiUrl = env.VITE_API_URL;
    if (!apiUrl) return { status: 'error' };

    try {
      const response = await fetchImpl(`${apiUrl.replace(/\/+$/, '')}/items/${itemId}`, {
        headers: { Accept: 'application/json' },
        signal: AbortSignal.timeout(apiTimeoutMs),
      });
      if (response.status === 404 || response.status === 403) return { status: 'not_found' };
      if (!response.ok) return { status: 'error' };

      const body = await response.json();
      return body?.data?.id ? { status: 'ok', item: body.data } : { status: 'error' };
    } catch {
      return { status: 'error' };
    }
  }

  return async function handler(req, res) {
    const headers = req.headers ?? {};
    const origin = getSiteOrigin(headers);
    const { itemId } = parseItemParams(req);
    const template = await loadTemplate(origin, headers);

    let statusCode = 200;
    let meta = buildDefaultMeta({ origin });
    let cacheable = template.fromOrigin;

    if (!isUuid(itemId)) {
      statusCode = 404; // sin llamar a la API: un id que no es UUID provoca un 500 en PostgreSQL
    } else {
      const result = await fetchItem(itemId);
      if (result.status === 'ok') meta = buildItemMeta(result.item, { origin, env });
      else if (result.status === 'not_found') statusCode = 404;
      else cacheable = false; // la API ha fallado: meta genéricas con 200, para no decir a los rastreadores que la ficha no existe
    }

    res.statusCode = statusCode;
    res.setHeader('Content-Type', 'text/html; charset=utf-8');
    // Solo se cachea con la plantilla real: así, aunque el CDN sirviera la respuesta a un navegador, este recibiría la SPA completa.
    res.setHeader('Cache-Control', cacheable && statusCode === 200 ? CACHE_CONTROL_OK : CACHE_CONTROL_NONE);
    res.end(injectMeta(template.html, meta));
  };
}
