/**
 * Meta tags, Open Graph y JSON-LD de las fichas, las listas y los perfiles públicos para los rastreadores (F2-10, F3-09).
 * La usa `api/meta.js`; la carpeta `_lib` no se publica como función en Vercel.
 */

/** Nombre del sitio en `og:site_name` y en el sufijo del título. */
export const SITE_NAME = 'Kudos';

/** Longitud máxima de la descripción (meta description y og:description). */
export const DESCRIPTION_MAX_LENGTH = 160;

/** Título por defecto: el mismo `<title>` que `index.html`. */
export const DEFAULT_TITLE = 'Kudos';

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

/** Slugs de las listas: solo [a-z0-9-] (`UserList::generateUniqueSlug`). */
const LIST_SLUG_PATTERN = /^[a-z0-9-]{1,255}$/;

/** Nombres de usuario que admite `GET /api/users/{username}`. */
const USERNAME_PATTERN = /^[A-Za-z0-9_]{1,30}$/;

/** Valor de `<meta name="robots">` y `X-Robots-Tag` para las páginas que no deben indexarse. */
export const ROBOTS_NOINDEX = 'noindex';

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

/** Indica si el valor tiene el formato de un slug de lista. */
export function isListSlug(value) {
  return typeof value === 'string' && LIST_SLUG_PATTERN.test(value);
}

/** Indica si el valor tiene el formato de un nombre de usuario. */
export function isUsername(value) {
  return typeof value === 'string' && USERNAME_PATTERN.test(value);
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

/** Número de títulos de una lista: cuenta solo los ítems visibles que devuelve la API. */
export function getListCount(list) {
  return Array.isArray(list?.items) ? list.items.length : (Number(list?.items_count) || 0);
}

/** Imagen de reserva de una lista con los datos de la propia lista: la miniatura de la portada o del primer ítem con imagen. */
export function getListThumbImage(list, env = {}) {
  const entries = Array.isArray(list?.items) ? list.items.filter((entry) => entry?.item) : [];
  const thumbOf = (entry) => entry?.item?.images?.[0]?.variants?.thumb;
  const entry = [
    entries.find((candidate) => candidate.item.id === list.cover_item_id) ?? entries[0],
    ...entries,
  ].find((candidate) => thumbOf(candidate) && resolveStorageUrl(thumbOf(candidate), env));
  if (!entry) return null;

  const image = entry.item.images[0];
  return { url: resolveStorageUrl(image.variants.thumb, env), alt: image.alt || entry.item.name };
}

/** Metadatos de una lista. */
export function buildListMeta(list, { origin, image = null }) {
  const count = getListCount(list);
  const countText = `${count} ${count === 1 ? 'título' : 'títulos'}`;
  const fallback = list.author?.name
    ? `Lista de ${list.author.name} en Kudos con ${countText}.`
    : list.is_editorial ? `Lista de Kudos con ${countText}.` : `Lista en Kudos con ${countText}.`;
  const description = truncateText(list.description) || fallback;
  const url = `${origin}/listas/${encodeURIComponent(list.slug)}`;
  const entries = (Array.isArray(list.items) ? list.items : []).filter((entry) => entry?.item);

  const jsonLd = {
    '@context': 'https://schema.org',
    '@type': 'ItemList',
    name: list.title,
    url,
    ...(truncateText(list.description) ? { description: truncateText(list.description) } : {}),
    numberOfItems: count,
    itemListOrder: list.is_ranked ? 'https://schema.org/ItemListOrderAscending' : 'https://schema.org/ItemListUnordered',
    ...(image ? { image: image.url } : {}),
    ...(list.author?.username ? {
      author: {
        '@type': 'Person',
        name: list.author.name,
        url: `${origin}/u/${encodeURIComponent(list.author.username)}`,
      },
    } : {}),
    itemListElement: entries.map((entry, index) => ({
      '@type': 'ListItem',
      position: index + 1,
      name: entry.item.name,
      ...(entry.item.category?.slug
        ? { url: `${origin}/${encodeURIComponent(entry.item.category.slug)}/item/${encodeURIComponent(entry.item.id)}` }
        : {}),
    })),
  };

  return {
    title: `${list.title} · ${SITE_NAME}`,
    ogTitle: list.title,
    description,
    url,
    image: image?.url ?? null,
    imageAlt: image?.alt ?? list.title,
    ogType: 'website',
    twitterCard: image ? 'summary_large_image' : 'summary',
    jsonLd,
    robots: list.visibility === 'unlisted' ? ROBOTS_NOINDEX : null,
  };
}

/** Metadatos de un perfil público. Lista blanca: solo `username`, `name`, `avatar`, `biography` y `member_since`. */
export function buildProfileMeta(profile, { origin, env = {} }) {
  const ogTitle = `${profile.name} (@${profile.username})`;
  const biography = truncateText(profile.biography);
  const description = biography || `Perfil de ${profile.name} en Kudos: sus valoraciones, listas y reseñas.`;
  const url = `${origin}/u/${encodeURIComponent(profile.username)}`;
  const imageUrl = resolveStorageUrl(profile.avatar, env);

  const jsonLd = {
    '@context': 'https://schema.org',
    '@type': 'ProfilePage',
    url,
    ...(profile.member_since ? { dateCreated: profile.member_since } : {}),
    mainEntity: {
      '@type': 'Person',
      name: profile.name,
      alternateName: `@${profile.username}`,
      url,
      ...(biography ? { description: biography } : {}),
      ...(imageUrl ? { image: imageUrl } : {}),
    },
  };

  return {
    title: `${ogTitle} · ${SITE_NAME}`,
    ogTitle,
    description,
    url,
    image: imageUrl,
    imageAlt: imageUrl ? `Avatar de ${profile.name}` : null,
    ogType: 'profile',
    twitterCard: 'summary', // el avatar es cuadrado
    jsonLd,
    robots: null,
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
    ...(meta.robots ? [name('robots', meta.robots)] : []),
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

/** Decide qué se pide: una lista, un perfil o una ficha de ítem (la query de `vercel.json`, `req.query` o la ruta original). */
export function parseRoute(req) {
  const pick = (value) => (Array.isArray(value) ? value[0] : value);
  const url = new URL(req.url ?? '/', 'http://localhost');
  const param = (key) => pick(req.query?.[key]) || url.searchParams.get(key) || null;
  const decode = (value) => {
    try {
      return decodeURIComponent(value);
    } catch {
      return null; // Ruta con codificación inválida: se ignora.
    }
  };

  const type = param('type');
  if (type === 'list') return { type: 'list', slug: param('slug') };
  if (type === 'profile') return { type: 'profile', username: param('username') };

  if (!type) {
    const list = url.pathname.match(/^\/listas\/([^/]+)\/?$/);
    if (list) return { type: 'list', slug: decode(list[1]) };
    const profile = url.pathname.match(/^\/u\/([^/]+)\/?$/);
    if (profile) return { type: 'profile', username: decode(profile[1]) };
  }

  return { type: 'item', ...parseItemParams(req) };
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

  async function fetchApi(path) {
    const apiUrl = env.VITE_API_URL;
    if (!apiUrl) return { status: 'error' };

    try {
      // Sin cabeceras de la petición: la API siempre se consulta como visitante anónimo.
      const response = await fetchImpl(`${apiUrl.replace(/\/+$/, '')}${path}`, {
        headers: { Accept: 'application/json' },
        signal: AbortSignal.timeout(apiTimeoutMs),
      });
      if (response.status === 404 || response.status === 403) return { status: 'not_found' };
      if (!response.ok) return { status: 'error' };

      const body = await response.json();
      return body?.data && typeof body.data === 'object' ? { status: 'ok', data: body.data } : { status: 'error' };
    } catch {
      return { status: 'error' };
    }
  }

  async function fetchItem(itemId) {
    const result = await fetchApi(`/items/${itemId}`);
    if (result.status !== 'ok') return result;
    return result.data.id ? { status: 'ok', item: result.data } : { status: 'error' };
  }

  async function fetchList(slug) {
    const result = await fetchApi(`/lists/${encodeURIComponent(slug)}`);
    if (result.status !== 'ok') return result;
    return result.data.slug ? result : { status: 'error' };
  }

  async function fetchProfile(username) {
    const result = await fetchApi(`/users/${encodeURIComponent(username)}`);
    if (result.status !== 'ok') return result;
    return result.data.username ? result : { status: 'error' };
  }

  /** Banner de la portada o, si no hay o no está disponible, del primer ítem; reserva: la miniatura de la propia lista. */
  async function resolveListImage(list) {
    const firstId = (Array.isArray(list.items) ? list.items : []).find((entry) => entry?.item)?.item.id;
    const candidates = [...new Set([list.cover_item_id, firstId].filter(isUuid))];
    let failed = false;

    for (const id of candidates) {
      const result = await fetchItem(id);
      if (result.status === 'error') failed = true;
      if (result.status !== 'ok') continue;

      const image = getItemImage(result.item, env);
      if (image) return { image, failed };
    }

    return { image: getListThumbImage(list, env), failed };
  }

  const genericPage = (statusCode, cacheable) => ({ statusCode, meta: null, cacheable });

  async function resolveItemPage(itemId, origin) {
    if (!isUuid(itemId)) return genericPage(404, true); // sin llamar a la API: un id que no es UUID provoca un 500 en PostgreSQL

    const result = await fetchItem(itemId);
    if (result.status === 'ok') return { statusCode: 200, meta: buildItemMeta(result.item, { origin, env }), cacheable: true };
    if (result.status === 'not_found') return genericPage(404, true);
    return genericPage(200, false); // la API ha fallado: meta genéricas con 200, para no decir a los rastreadores que la ficha no existe
  }

  async function resolveListPage(slug, origin) {
    if (!isListSlug(slug)) return genericPage(404, true); // sin llamar a la API

    const result = await fetchList(slug);
    if (result.status === 'not_found') return genericPage(404, true);
    if (result.status !== 'ok') return genericPage(200, false);

    const list = result.data;
    // Defensa en profundidad: sin sesión la API no devuelve listas privadas ni ocultadas, pero no se usa nada de ellas.
    if (list.visibility === 'private' || list.is_hidden_by_moderation === true) return genericPage(404, true);

    const { image, failed } = await resolveListImage(list);
    return { statusCode: 200, meta: buildListMeta(list, { origin, image }), cacheable: !failed };
  }

  async function resolveProfilePage(username, origin) {
    if (!isUsername(username)) return genericPage(404, true); // sin llamar a la API

    const result = await fetchProfile(username);
    if (result.status === 'not_found') return genericPage(404, true);
    if (result.status !== 'ok') return genericPage(200, false);

    // Defensa en profundidad: la API devuelve 404 para los perfiles privados.
    if (result.data.is_public !== true) return genericPage(404, true);

    return { statusCode: 200, meta: buildProfileMeta(result.data, { origin, env }), cacheable: true };
  }

  return async function handler(req, res) {
    const headers = req.headers ?? {};
    const origin = getSiteOrigin(headers);
    const route = parseRoute(req);
    const template = await loadTemplate(origin, headers);

    let page;
    if (route.type === 'list') page = await resolveListPage(route.slug, origin);
    else if (route.type === 'profile') page = await resolveProfilePage(route.username, origin);
    else page = await resolveItemPage(route.itemId, origin);

    const meta = page.meta ?? buildDefaultMeta({ origin });
    // Solo se cachea con la plantilla real: así, aunque el CDN sirviera la respuesta a un navegador, este recibiría la SPA completa.
    const cacheable = template.fromOrigin && page.cacheable;

    res.statusCode = page.statusCode;
    res.setHeader('Content-Type', 'text/html; charset=utf-8');
    res.setHeader('Cache-Control', cacheable && page.statusCode === 200 ? CACHE_CONTROL_OK : CACHE_CONTROL_NONE);
    if (meta.robots) res.setHeader('X-Robots-Tag', meta.robots);
    res.end(injectMeta(template.html, meta));
  };
}
