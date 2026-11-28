import { test } from 'node:test';
import assert from 'node:assert/strict';
import {
  CACHE_CONTROL_NONE,
  CACHE_CONTROL_OK,
  DEFAULT_DESCRIPTION,
  buildItemMeta,
  createMetaHandler,
  escapeHtml,
  getItemImage,
  injectMeta,
  isUuid,
  resolveStorageUrl,
  truncateText,
} from '../../api/_lib/seo.js';

const ITEM_ID = '01a18c59-f943-7066-a3eb-c6abc586883b';
const ENV = {
  VITE_API_URL: 'https://api.example.test/api',
  VITE_STORAGE_URL: 'https://api.example.test/storage/',
};
const TEMPLATE = `<!doctype html>
<html lang="es">
  <head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <link rel="icon" type="image/svg+xml" href="/assets/favicon-CYvQuHf9.svg" />
    <link rel="alternate icon" href="/assets/favicon-BuU_IiDV.ico" />
    <title>Kudos</title>
    <script type="module" crossorigin src="/assets/index-test.js"></script>
    <link rel="modulepreload" crossorigin href="/assets/vendor-react-test.js">
    <link rel="stylesheet" crossorigin href="/assets/index-test.css">
  </head>
  <body>
    <div id="root"></div>
  </body>
</html>
`;
const LONG_DESCRIPTION = 'Un torneo de artes marciales reúne a los mejores luchadores del mundo en una competición '
  + 'que cambiará para siempre el destino de todos ellos y de quienes los rodean, con combates espectaculares.';

function makeItem(overrides = {}) {
  return {
    id: ITEM_ID,
    name: 'Street Fighter',
    original_title: 'Street Fighter',
    description: LONG_DESCRIPTION,
    released_at: '2026-10-13',
    vote_avg: 7.25,
    vote_count: 4,
    category: { slug: 'peliculas', name: 'Películas' },
    images: [
      {
        alt: 'Póster',
        meta: { orientation: 'portrait' },
        variants: { thumb: 'items/peliculas/x/poster-thumb.webp', banner: 'items/peliculas/x/poster-banner.webp' },
      },
      {
        alt: 'Fondo',
        meta: { orientation: 'landscape' },
        variants: { thumb: 'items/peliculas/x/backdrop-thumb.webp', banner: 'items/peliculas/x/backdrop-banner.webp' },
      },
    ],
    ...overrides,
  };
}

function makeRes() {
  return {
    statusCode: 200,
    headers: {},
    body: null,
    setHeader(key, value) { this.headers[key.toLowerCase()] = value; },
    end(body) { this.body = body; },
  };
}

function makeFetch({ template = TEMPLATE, item = makeItem(), apiStatus = 200, templateFails = false, apiThrows = false } = {}) {
  const calls = [];
  const fetchImpl = async (url, options) => {
    calls.push({ url, options });
    if (url.endsWith('/index.html')) {
      if (templateFails) throw new Error('template unavailable');
      return new Response(template, { status: 200 });
    }
    if (url.includes('/items/')) {
      if (apiThrows) throw new Error('api unavailable');
      return new Response(JSON.stringify({ data: item }), {
        status: apiStatus,
        headers: { 'Content-Type': 'application/json' },
      });
    }
    throw new Error(`Unexpected request: ${url}`);
  };
  fetchImpl.calls = calls;
  return fetchImpl;
}

function makeReq(url, headers = {}) {
  return { url, headers: { host: 'kudos.example.test', 'x-forwarded-proto': 'https', ...headers } };
}

const itemUrl = `/api/meta?categorySlug=peliculas&itemId=${ITEM_ID}`;
const itemCalls = (fetchImpl) => fetchImpl.calls.filter((call) => call.url.includes('/items/'));
const templateCalls = (fetchImpl) => fetchImpl.calls.filter((call) => call.url.endsWith('/index.html'));

test('escape_html_escapes_special_characters', () => {
  const escaped = escapeHtml(`<a href="x">'&'</a>`);
  assert.doesNotMatch(escaped, /[<>"']/);
  assert.equal(escaped, '&lt;a href=&quot;x&quot;&gt;&#39;&amp;&#39;&lt;/a&gt;');
  assert.equal(escapeHtml(null), '');
});

test('is_uuid_accepts_only_uuids', () => {
  assert.equal(isUuid(ITEM_ID), true);
  for (const value of ['123', '../etc', '', undefined]) {
    assert.equal(isUuid(value), false);
  }
});

test('truncate_text_collapses_whitespace_and_cuts_at_word_boundary', () => {
  assert.equal(truncateText('  a\n\n b  '), 'a b');

  const words = Array.from({ length: 60 }, (_, i) => `palabra${i}`);
  const text = words.join(' ').slice(0, 300);
  const result = truncateText(text);
  assert.ok(result.length <= 160);
  assert.ok(result.endsWith('…'));
  const body = result.slice(0, -1);
  assert.ok(text.startsWith(`${body} `), 'corta en el límite de una palabra');
  assert.ok(text.split(' ').includes(body.split(' ').at(-1)), 'la última palabra está completa');
});

test('resolve_storage_url_builds_absolute_urls', () => {
  const path = 'items/peliculas/x/a-banner.webp';
  const expected = 'https://api.example.test/storage/items/peliculas/x/a-banner.webp';
  assert.equal(resolveStorageUrl(path, { VITE_STORAGE_URL: 'https://api.example.test/storage/' }), expected);
  assert.equal(resolveStorageUrl(path, { VITE_STORAGE_URL: 'https://api.example.test/storage' }), expected);
  assert.equal(resolveStorageUrl('https://cdn.example.test/a.webp', ENV), 'https://cdn.example.test/a.webp');
  assert.equal(resolveStorageUrl(path, { VITE_API_URL: 'https://api.example.test/api' }), expected);
  assert.equal(resolveStorageUrl(path, {}), null);
});

test('get_item_image_prefers_landscape_banner', () => {
  assert.equal(getItemImage(makeItem(), ENV).url, 'https://api.example.test/storage/items/peliculas/x/backdrop-banner.webp');

  const onlyPoster = makeItem({ images: [makeItem().images[0]] });
  assert.equal(getItemImage(onlyPoster, ENV).url, 'https://api.example.test/storage/items/peliculas/x/poster-banner.webp');

  const onlyThumb = makeItem({ images: [{ alt: null, meta: {}, variants: { thumb: 'items/peliculas/x/only-thumb.webp' } }] });
  assert.equal(getItemImage(onlyThumb, ENV).url, 'https://api.example.test/storage/items/peliculas/x/only-thumb.webp');

  assert.equal(getItemImage(makeItem({ images: [] }), ENV), null);
});

test('build_item_meta_maps_category_to_schema_type', () => {
  const origin = 'https://kudos.example.test';
  const build = (slug) => buildItemMeta(makeItem({ category: { slug, name: slug } }), { origin, env: ENV });

  assert.equal(build('peliculas').jsonLd['@type'], 'Movie');
  assert.equal(build('peliculas').ogType, 'video.movie');
  assert.equal(build('series').jsonLd['@type'], 'TVSeries');
  assert.equal(build('series').ogType, 'video.tv_show');
  assert.equal(build('videojuegos').jsonLd['@type'], 'VideoGame');
  assert.equal(build('videojuegos').ogType, 'website');

  const place = build('destinos');
  assert.equal(place.jsonLd['@type'], 'Place');
  assert.equal(place.ogType, 'website');
  assert.equal(place.jsonLd.datePublished, undefined);
  assert.equal(place.ogTitle, 'Street Fighter');

  assert.equal(build('otra').jsonLd['@type'], 'CreativeWork');
});

test('build_item_meta_builds_title_url_and_image', () => {
  const meta = buildItemMeta(makeItem(), { origin: 'https://kudos.example.test', env: ENV });
  assert.equal(meta.title, 'Street Fighter (2026) · Kudos');
  assert.equal(meta.ogTitle, 'Street Fighter (2026)');
  assert.equal(meta.url, `https://kudos.example.test/peliculas/item/${ITEM_ID}`);
  assert.equal(meta.image, 'https://api.example.test/storage/items/peliculas/x/backdrop-banner.webp');
  assert.equal(meta.twitterCard, 'summary_large_image');
  assert.ok(meta.description.length <= 160);
});

test('build_item_meta_includes_aggregate_rating_only_with_votes', () => {
  const origin = 'https://kudos.example.test';
  const withVotes = buildItemMeta(makeItem(), { origin, env: ENV }).jsonLd.aggregateRating;
  assert.equal(withVotes.ratingValue, 7.3);
  assert.equal(withVotes.ratingCount, 4);
  assert.equal(withVotes.bestRating, 10);
  assert.equal(withVotes.worstRating, 0);

  const withoutVotes = buildItemMeta(makeItem({ vote_count: 0 }), { origin, env: ENV });
  assert.equal(withoutVotes.jsonLd.aggregateRating, undefined);
});

test('build_item_meta_uses_fallback_description_without_description', () => {
  const origin = 'https://kudos.example.test';
  const meta = buildItemMeta(makeItem({ description: null, images: [] }), { origin, env: ENV });
  assert.equal(meta.description, 'Street Fighter en Kudos: mira su nota media y deja tu valoración.');
  assert.equal(meta.jsonLd.description, undefined);
  assert.equal(meta.twitterCard, 'summary');
});

test('inject_meta_replaces_title_and_inserts_tags_before_head_end', () => {
  const item = makeItem({ name: 'Cash $& Co' });
  const meta = buildItemMeta(item, { origin: 'https://kudos.example.test', env: ENV });
  const html = injectMeta(TEMPLATE, meta);

  assert.equal(html.match(/<title>/g).length, 1);
  assert.ok(html.includes('<title>Cash $&amp; Co (2026) · Kudos</title>'));
  const head = html.slice(0, html.indexOf('</head>'));
  for (const needle of ['og:title', 'og:image', 'twitter:card', 'application/ld+json']) {
    assert.ok(head.includes(needle), `${needle} debe estar en el head`);
  }
  assert.ok(html.includes('<script type="module"'));
  assert.ok(html.includes('<div id="root"></div>'));
  assert.ok(html.includes('content="Cash $&amp; Co (2026)"'));
});

test('inject_meta_uses_fallback_template_without_head', () => {
  const meta = buildItemMeta(makeItem(), { origin: 'https://kudos.example.test', env: ENV });
  const html = injectMeta('<p>x</p>', meta);
  assert.ok(html.includes('<div id="root"></div>'));
  assert.ok(html.includes('property="og:title"'));
  assert.ok(html.includes('name="description"'));
});

test('serialize_json_ld_escapes_script_closing', () => {
  const name = '</script><script>alert(1)</script>';
  const meta = buildItemMeta(makeItem({ name }), { origin: 'https://kudos.example.test', env: ENV });
  const html = injectMeta(TEMPLATE, meta);

  const start = html.indexOf('<script type="application/ld+json">');
  assert.notEqual(start, -1);
  const contentStart = start + '<script type="application/ld+json">'.length;
  const end = html.indexOf('</script>', contentStart);
  const content = html.slice(contentStart, end);
  const block = html.slice(start, end + '</script>'.length);
  assert.equal(block.split('</script>').length - 1, 1);
  assert.ok(!content.includes('</script>'));
  assert.equal(JSON.parse(content).name, name);
});

test('handler_returns_item_meta_with_compiled_template', async () => {
  const fetchImpl = makeFetch();
  const handler = createMetaHandler({ fetchImpl, env: ENV });
  const res = makeRes();
  await handler(makeReq(itemUrl), res);

  assert.equal(res.statusCode, 200);
  assert.ok(res.headers['content-type'].startsWith('text/html'));
  assert.equal(res.headers['cache-control'], CACHE_CONTROL_OK);
  assert.ok(res.body.includes('property="og:title" content="Street Fighter (2026)"'));
  assert.ok(res.body.includes('https://api.example.test/storage/items/peliculas/x/backdrop-banner.webp'));
  assert.ok(res.body.includes('"@type":"Movie"'));
  assert.ok(res.body.includes('<script type="module"'));

  const api = itemCalls(fetchImpl);
  assert.equal(api.length, 1);
  assert.equal(api[0].url, `https://api.example.test/api/items/${ITEM_ID}`);
  assert.equal(api[0].options.headers.Accept, 'application/json');
  assert.equal(templateCalls(fetchImpl)[0].url, 'https://kudos.example.test/index.html');
});

test('handler_reads_params_from_path_when_query_missing', async () => {
  const handler = createMetaHandler({ fetchImpl: makeFetch(), env: ENV });
  const res = makeRes();
  await handler(makeReq(`/peliculas/item/${ITEM_ID}`), res);

  assert.equal(res.statusCode, 200);
  assert.ok(res.body.includes('property="og:title" content="Street Fighter (2026)"'));
});

test('handler_returns_404_for_missing_item', async () => {
  for (const apiStatus of [404, 403]) {
    const handler = createMetaHandler({ fetchImpl: makeFetch({ apiStatus }), env: ENV });
    const res = makeRes();
    await handler(makeReq(itemUrl), res);

    assert.equal(res.statusCode, 404);
    assert.ok(res.body.includes('<title>Kudos</title>'));
    assert.ok(!res.body.includes('application/ld+json'));
    assert.equal(res.headers['cache-control'], CACHE_CONTROL_NONE);
  }
});

test('handler_returns_404_without_calling_api_for_invalid_id', async () => {
  const fetchImpl = makeFetch();
  const handler = createMetaHandler({ fetchImpl, env: ENV });
  const res = makeRes();
  await handler(makeReq('/api/meta?categorySlug=peliculas&itemId=no-es-uuid'), res);

  assert.equal(res.statusCode, 404);
  assert.equal(itemCalls(fetchImpl).length, 0);
});

test('handler_falls_back_to_generic_meta_when_api_fails', async () => {
  const cases = [
    { fetchImpl: makeFetch({ apiStatus: 500 }), env: ENV, expectsApiCall: true },
    { fetchImpl: makeFetch({ apiThrows: true }), env: ENV, expectsApiCall: true },
    { fetchImpl: makeFetch(), env: {}, expectsApiCall: false },
  ];

  for (const { fetchImpl, env, expectsApiCall } of cases) {
    const handler = createMetaHandler({ fetchImpl, env });
    const res = makeRes();
    await handler(makeReq(itemUrl), res);

    assert.equal(res.statusCode, 200);
    assert.ok(res.body.includes(DEFAULT_DESCRIPTION));
    assert.equal(res.headers['cache-control'], CACHE_CONTROL_NONE);
    assert.equal(itemCalls(fetchImpl).length > 0, expectsApiCall);
  }
});

test('handler_uses_fallback_template_when_index_fetch_fails', async () => {
  const fetchImpl = makeFetch({ templateFails: true });
  const handler = createMetaHandler({ fetchImpl, env: ENV });

  const first = makeRes();
  await handler(makeReq(itemUrl), first);
  assert.equal(first.statusCode, 200);
  assert.ok(first.body.includes('<div id="root"></div>'));
  assert.ok(first.body.includes('og:title'));
  assert.equal(first.headers['cache-control'], CACHE_CONTROL_NONE);

  await handler(makeReq(itemUrl), makeRes());
  assert.equal(templateCalls(fetchImpl).length, 2);
});

test('handler_caches_template_between_requests', async () => {
  const fetchImpl = makeFetch();
  const handler = createMetaHandler({ fetchImpl, env: ENV });

  await handler(makeReq(itemUrl), makeRes());
  await handler(makeReq(itemUrl), makeRes());

  assert.equal(templateCalls(fetchImpl).length, 1);
  assert.equal(itemCalls(fetchImpl).length, 2);
});

test('handler_forwards_protection_bypass_header_to_template_fetch', async () => {
  const fetchImpl = makeFetch();
  const handler = createMetaHandler({ fetchImpl, env: ENV });
  await handler(makeReq(itemUrl, { 'x-vercel-protection-bypass': 'secreto', cookie: 'a=b' }), makeRes());

  const templateHeaders = templateCalls(fetchImpl)[0].options.headers;
  assert.equal(templateHeaders['x-vercel-protection-bypass'], 'secreto');
  assert.equal(templateHeaders.cookie, 'a=b');

  const apiHeaders = itemCalls(fetchImpl)[0].options.headers;
  assert.equal(apiHeaders['x-vercel-protection-bypass'], undefined);
  assert.equal(apiHeaders.cookie, undefined);
});
