import { test } from 'node:test';
import assert from 'node:assert/strict';
import {
  CACHE_CONTROL_NONE,
  CACHE_CONTROL_OK,
  DEFAULT_DESCRIPTION,
  buildListMeta,
  buildProfileMeta,
  createMetaHandler,
  injectMeta,
  isListSlug,
  isUsername,
  parseRoute,
  renderMetaTags,
} from '../../api/_lib/seo.js';

const ORIGIN = 'https://kudos.example.test';
const FIRST_ID = '01a18c59-f943-7066-a3eb-c6abc586883b';
const COVER_ID = '01a18c59-f943-7066-a3eb-c6abc586883c';
const SLUG = 'mejores-pelis-de-2026';
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
    <title>Proyecto Kudos</title>
    <script type="module" crossorigin src="/assets/index-test.js"></script>
    <link rel="modulepreload" crossorigin href="/assets/vendor-react-test.js">
    <link rel="stylesheet" crossorigin href="/assets/index-test.css">
  </head>
  <body>
    <div id="root"></div>
  </body>
</html>
`;

function makeListItem(id, name) {
  return {
    id,
    name,
    category: { slug: 'peliculas' },
    images: [
      {
        alt: null,
        meta: { orientation: 'portrait' },
        variants: { thumb: `items/peliculas/${id}/poster-thumb.webp` },
      },
    ],
  };
}

function makeList(overrides = {}) {
  return {
    id: 'lista-1',
    title: 'Mejores pelis de 2026',
    slug: SLUG,
    description: null,
    visibility: 'public',
    is_ranked: true,
    is_editorial: false,
    items_count: 2,
    likes_count: 0,
    cover_item_id: COVER_ID,
    is_hidden_by_moderation: false,
    author: { username: 'ana_f309', name: 'Ana', avatar: null },
    items: [
      { id: 'e1', position: 1, note: null, item: makeListItem(FIRST_ID, 'Primera peli') },
      { id: 'e2', position: 2, note: null, item: makeListItem(COVER_ID, 'Peli de portada') },
    ],
    ...overrides,
  };
}

function makeItemDetail(id, name) {
  return {
    id,
    name,
    category: { slug: 'peliculas' },
    images: [
      {
        alt: 'Póster',
        meta: { orientation: 'portrait' },
        variants: { thumb: `items/peliculas/${id}/poster-thumb.webp`, banner: `items/peliculas/${id}/poster-banner.webp` },
      },
      {
        alt: 'Fondo',
        meta: { orientation: 'landscape' },
        variants: { thumb: `items/peliculas/${id}/backdrop-thumb.webp`, banner: `items/peliculas/${id}/backdrop-banner.webp` },
      },
    ],
  };
}

function makeProfile(overrides = {}) {
  return {
    id: 'u1',
    username: 'ana_f309',
    name: 'Ana',
    avatar: 'avatars/u1/avatar.webp',
    biography: 'Cinéfila.',
    is_public: true,
    is_owner: false,
    member_since: '2026-01-01T00:00:00+00:00',
    top_by_category: [],
    lists: [{ title: 'Lista secreta TOPSECRET' }],
    reviews: [],
    // Campos que la API real nunca envía y que no deben salir si llegaran.
    email: 'ana@example.test',
    city: 'Ciudad-Trampa',
    birth_date: '2000-02-02',
    social_links: { x: 'https://x.example.test/ana' },
    interests: ['interes-trampa'],
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

function makeJsonResponse(entry) {
  if (entry === 'throw') throw new Error('api unavailable');
  return new Response(JSON.stringify({ data: entry.data }), {
    status: entry.status,
    headers: { 'Content-Type': 'application/json' },
  });
}

/** fetch simulado: la plantilla, y listas, usuarios e ítems según los mapas (`{ status, data }` o `'throw'`). */
function makeRoutedFetch({ template = TEMPLATE, lists = {}, users = {}, items = {} } = {}) {
  const calls = [];
  const fetchImpl = async (url, options) => {
    calls.push({ url, options });
    if (url.endsWith('/index.html')) return new Response(template, { status: 200 });

    for (const [segment, map] of [['/lists/', lists], ['/users/', users], ['/items/', items]]) {
      const index = url.indexOf(segment);
      if (index !== -1) {
        const entry = map[decodeURIComponent(url.slice(index + segment.length))];
        if (entry) return makeJsonResponse(entry);
        return new Response('{}', { status: 404 });
      }
    }
    throw new Error(`Unexpected request: ${url}`);
  };
  fetchImpl.calls = calls;
  return fetchImpl;
}

function makeReq(url, headers = {}) {
  return { url, headers: { host: 'kudos.example.test', 'x-forwarded-proto': 'https', ...headers } };
}

const listUrl = `/api/meta?type=list&slug=${SLUG}`;
const profileUrl = '/api/meta?type=profile&username=ana_f309';
const apiCalls = (fetchImpl) => fetchImpl.calls.filter((call) => !call.url.endsWith('/index.html'));
const itemCalls = (fetchImpl) => fetchImpl.calls.filter((call) => call.url.includes('/items/'));
const bannerUrl = (id) => `https://api.example.test/storage/items/peliculas/${id}/backdrop-banner.webp`;

async function run(url, options = {}, headers = {}) {
  const fetchImpl = makeRoutedFetch(options.fetch);
  const handler = createMetaHandler({ fetchImpl, env: options.env ?? ENV });
  const res = makeRes();
  await handler(makeReq(url, headers), res);
  return { res, fetchImpl };
}

const okList = (list = makeList()) => ({ [list.slug ?? SLUG]: { status: 200, data: list } });
const okItems = () => ({
  [COVER_ID]: { status: 200, data: makeItemDetail(COVER_ID, 'Peli de portada') },
  [FIRST_ID]: { status: 200, data: makeItemDetail(FIRST_ID, 'Primera peli') },
});

test('is_list_slug_and_is_username_validate_format', () => {
  assert.equal(isListSlug('mejores-pelis-2'), true);
  for (const value of ['Mejores', '../x', 'a b', '', undefined, 'a'.repeat(256)]) {
    assert.equal(isListSlug(value), false, String(value));
  }

  assert.equal(isUsername('Ana_F309'), true);
  for (const value of ['ana-x', 'ana.x', 'a'.repeat(31), '']) {
    assert.equal(isUsername(value), false, String(value));
  }
});

test('parse_route_detects_list_profile_and_item_routes', () => {
  assert.deepEqual(parseRoute({ url: '/api/meta?type=list&slug=mi-lista' }), { type: 'list', slug: 'mi-lista' });
  assert.deepEqual(parseRoute({ url: '/api/meta?type=profile&username=ana' }), { type: 'profile', username: 'ana' });
  assert.deepEqual(parseRoute({ url: '/listas/mi-lista' }), { type: 'list', slug: 'mi-lista' });
  assert.deepEqual(parseRoute({ url: '/u/ana' }), { type: 'profile', username: 'ana' });

  const item = { type: 'item', categorySlug: 'peliculas', itemId: FIRST_ID };
  assert.deepEqual(parseRoute({ url: `/api/meta?categorySlug=peliculas&itemId=${FIRST_ID}` }), item);
  assert.deepEqual(parseRoute({ url: `/peliculas/item/${FIRST_ID}` }), item);

  assert.deepEqual(parseRoute({ url: '/api/meta', query: { type: 'list', slug: 'mi-lista' } }), { type: 'list', slug: 'mi-lista' });
  assert.deepEqual(
    parseRoute({ url: '/api/meta', query: { type: ['profile'], username: ['ana'] } }),
    { type: 'profile', username: 'ana' },
  );
  assert.deepEqual(
    parseRoute({ url: '/api/meta', query: { categorySlug: 'peliculas', itemId: FIRST_ID } }),
    item,
  );
});

test('build_list_meta_builds_title_url_image_and_json_ld', () => {
  const image = { url: bannerUrl(COVER_ID), alt: 'Fondo' };
  const meta = buildListMeta(makeList(), { origin: ORIGIN, image });

  assert.equal(meta.title, 'Mejores pelis de 2026 · Kudos');
  assert.equal(meta.ogTitle, 'Mejores pelis de 2026');
  assert.equal(meta.url, `${ORIGIN}/listas/${SLUG}`);
  assert.equal(meta.image, image.url);
  assert.equal(meta.imageAlt, 'Fondo');
  assert.equal(meta.twitterCard, 'summary_large_image');
  assert.equal(meta.jsonLd['@type'], 'ItemList');
  assert.equal(meta.jsonLd.numberOfItems, 2);
  assert.equal(meta.jsonLd.itemListOrder, 'https://schema.org/ItemListOrderAscending');
  assert.equal(meta.jsonLd.itemListElement[0].position, 1);
  assert.equal(meta.jsonLd.itemListElement[0].url, `${ORIGIN}/peliculas/item/${FIRST_ID}`);
  assert.equal(meta.jsonLd.author.url, `${ORIGIN}/u/ana_f309`);
  assert.equal(meta.robots, null);

  const unranked = buildListMeta(makeList({ is_ranked: false }), { origin: ORIGIN });
  assert.equal(unranked.jsonLd.itemListOrder, 'https://schema.org/ItemListUnordered');
  assert.equal(unranked.twitterCard, 'summary');
  assert.equal(unranked.image, null);
});

test('build_list_meta_uses_fallback_descriptions', () => {
  const build = (overrides) => buildListMeta(makeList(overrides), { origin: ORIGIN });

  assert.equal(build({}).description, 'Lista de Ana en Kudos con 2 títulos.');
  assert.equal(build({ author: null, is_editorial: true }).description, 'Lista de Kudos con 2 títulos.');
  assert.equal(build({ author: null, is_editorial: false }).description, 'Lista en Kudos con 2 títulos.');
  assert.equal(build({ items: [makeList().items[0]] }).description, 'Lista de Ana en Kudos con 1 título.');

  const long = build({ description: 'palabra '.repeat(60), author: null });
  assert.ok(long.description.length <= 160);
  assert.ok(long.description.endsWith('…'));
  assert.equal(long.jsonLd.author, undefined);
});

test('build_list_meta_marks_unlisted_lists_noindex', () => {
  const unlisted = buildListMeta(makeList({ visibility: 'unlisted' }), { origin: ORIGIN });
  assert.equal(unlisted.robots, 'noindex');
  assert.ok(renderMetaTags(unlisted).includes('<meta name="robots" content="noindex">'));

  const pub = buildListMeta(makeList(), { origin: ORIGIN });
  assert.ok(!renderMetaTags(pub).some((tag) => tag.includes('name="robots"')));
});

test('build_profile_meta_uses_only_public_fields', () => {
  const meta = buildProfileMeta(makeProfile(), { origin: ORIGIN, env: ENV });
  const html = injectMeta(TEMPLATE, meta);

  assert.ok(html.includes('Ana (@ana_f309) · Kudos'));
  assert.ok(html.includes('property="og:type" content="profile"'));
  assert.ok(html.includes('Cinéfila.'));
  assert.ok(html.includes('https://api.example.test/storage/avatars/u1/avatar.webp'));
  assert.ok(html.includes('"@type":"ProfilePage"'));

  for (const secret of ['ana@example.test', 'Ciudad-Trampa', '2000-02-02', 'x.example.test', 'interes-trampa', 'TOPSECRET']) {
    assert.ok(!html.includes(secret), `no debe aparecer ${secret}`);
  }
});

test('build_profile_meta_without_avatar_or_biography', () => {
  const meta = buildProfileMeta(makeProfile({ avatar: null, biography: null }), { origin: ORIGIN, env: ENV });
  assert.equal(meta.image, null);
  assert.equal(meta.twitterCard, 'summary');
  assert.equal(meta.description, 'Perfil de Ana en Kudos: sus valoraciones, listas y reseñas.');
  assert.ok(!renderMetaTags(meta).some((tag) => tag.includes('og:image')));
});

test('handler_returns_list_meta_with_cover_item_banner', async () => {
  const { res, fetchImpl } = await run(
    listUrl,
    { fetch: { lists: okList(), items: okItems() } },
    { cookie: 'sesion=abc', 'x-vercel-protection-bypass': 'secreto' },
  );

  assert.equal(res.statusCode, 200);
  assert.equal(res.headers['cache-control'], CACHE_CONTROL_OK);
  assert.ok(res.body.includes('property="og:title" content="Mejores pelis de 2026"'));
  assert.ok(res.body.includes(`property="og:image" content="${bannerUrl(COVER_ID)}"`));
  assert.ok(res.body.includes('"@type":"ItemList"'));
  assert.ok(res.body.includes('<script type="module"'));
  assert.equal(res.headers['x-robots-tag'], undefined);

  assert.deepEqual(
    apiCalls(fetchImpl).map((call) => call.url),
    [`https://api.example.test/api/lists/${SLUG}`, `https://api.example.test/api/items/${COVER_ID}`],
  );
  for (const call of apiCalls(fetchImpl)) {
    assert.equal(call.options.headers.cookie, undefined);
    assert.equal(call.options.headers['x-vercel-protection-bypass'], undefined);
  }
});

test('handler_uses_first_item_banner_when_list_has_no_cover', async () => {
  const { res, fetchImpl } = await run(listUrl, {
    fetch: { lists: okList(makeList({ cover_item_id: null })), items: okItems() },
  });

  assert.equal(res.statusCode, 200);
  assert.ok(res.body.includes(`property="og:image" content="${bannerUrl(FIRST_ID)}"`));
  assert.equal(itemCalls(fetchImpl).length, 1);
});

test('handler_falls_back_to_first_item_when_cover_item_is_unavailable', async () => {
  const { res, fetchImpl } = await run(listUrl, {
    fetch: {
      lists: okList(),
      items: { [COVER_ID]: { status: 403, data: null }, [FIRST_ID]: okItems()[FIRST_ID] },
    },
  });

  assert.equal(res.statusCode, 200);
  assert.equal(res.headers['cache-control'], CACHE_CONTROL_OK);
  assert.ok(res.body.includes(`property="og:image" content="${bannerUrl(FIRST_ID)}"`));
  assert.equal(itemCalls(fetchImpl).length, 2);
});

test('handler_uses_list_thumb_when_item_details_fail', async () => {
  const failures = [
    { [COVER_ID]: { status: 500, data: null }, [FIRST_ID]: { status: 500, data: null } },
    { [COVER_ID]: 'throw', [FIRST_ID]: 'throw' },
  ];

  for (const items of failures) {
    const { res } = await run(listUrl, { fetch: { lists: okList(), items } });

    assert.equal(res.statusCode, 200);
    assert.ok(res.body.includes(`property="og:image" content="https://api.example.test/storage/items/peliculas/${COVER_ID}/poster-thumb.webp"`));
    assert.equal(res.headers['cache-control'], CACHE_CONTROL_NONE);
  }
});

test('handler_marks_unlisted_list_noindex', async () => {
  const { res } = await run(listUrl, {
    fetch: { lists: okList(makeList({ visibility: 'unlisted' })), items: okItems() },
  });

  assert.equal(res.statusCode, 200);
  const head = res.body.slice(0, res.body.indexOf('</head>'));
  assert.ok(head.includes('<meta name="robots" content="noindex">'));
  assert.equal(res.headers['x-robots-tag'], 'noindex');
});

test('handler_returns_404_without_leaking_private_list', async () => {
  const { res: missing } = await run(listUrl, { fetch: { lists: {} } });
  assert.equal(missing.statusCode, 404);
  assert.ok(missing.body.includes('<title>Proyecto Kudos</title>'));
  assert.ok(!missing.body.includes('application/ld+json'));
  assert.equal(missing.headers['cache-control'], CACHE_CONTROL_NONE);

  const secret = { title: 'Titulo-Secreto-TOPSECRET', description: 'Descripcion-Secreta-TOPSECRET' };
  for (const overrides of [{ visibility: 'private' }, { is_hidden_by_moderation: true }]) {
    const { res, fetchImpl } = await run(listUrl, {
      fetch: { lists: okList(makeList({ ...secret, ...overrides })), items: okItems() },
    });

    assert.equal(res.statusCode, 404);
    assert.ok(!res.body.includes('TOPSECRET'));
    assert.ok(res.body.includes('<title>Proyecto Kudos</title>'));
    assert.equal(res.headers['cache-control'], CACHE_CONTROL_NONE);
    assert.equal(itemCalls(fetchImpl).length, 0);
  }
});

test('handler_returns_404_without_calling_api_for_invalid_list_slug_or_username', async () => {
  const urls = [
    '/api/meta?type=list&slug=Mi_Lista',
    '/api/meta?type=list&slug=..%2Fx',
    '/api/meta?type=profile&username=ana-x',
    `/api/meta?type=profile&username=${'a'.repeat(31)}`,
  ];

  for (const url of urls) {
    const { res, fetchImpl } = await run(url, { fetch: { lists: okList(), users: { ana_f309: { status: 200, data: makeProfile() } } } });

    assert.equal(res.statusCode, 404, url);
    assert.equal(apiCalls(fetchImpl).length, 0, url);
  }
});

test('handler_returns_profile_meta', async () => {
  for (const url of [profileUrl, '/api/meta?type=profile&username=Ana_F309']) {
    const { res, fetchImpl } = await run(url, {
      fetch: { users: { ana_f309: { status: 200, data: makeProfile() }, Ana_F309: { status: 200, data: makeProfile() } } },
    });

    assert.equal(res.statusCode, 200);
    assert.equal(res.headers['cache-control'], CACHE_CONTROL_OK);
    assert.ok(res.body.includes('property="og:type" content="profile"'));
    assert.ok(res.body.includes('name="twitter:card" content="summary"'));
    assert.ok(res.body.includes('https://api.example.test/storage/avatars/u1/avatar.webp'));
    assert.ok(res.body.includes(`<link rel="canonical" href="${ORIGIN}/u/ana_f309">`));
    assert.equal(apiCalls(fetchImpl).length, 1);
  }

  const { fetchImpl } = await run(profileUrl, { fetch: { users: { ana_f309: { status: 200, data: makeProfile() } } } });
  assert.equal(apiCalls(fetchImpl)[0].url, 'https://api.example.test/api/users/ana_f309');
});

test('handler_returns_404_for_private_or_missing_profile', async () => {
  const { res: missing } = await run(profileUrl, { fetch: { users: {} } });
  assert.equal(missing.statusCode, 404);
  assert.ok(missing.body.includes('<title>Proyecto Kudos</title>'));
  assert.ok(missing.body.includes(DEFAULT_DESCRIPTION));
  assert.equal(missing.headers['cache-control'], CACHE_CONTROL_NONE);

  const { res: hidden } = await run(profileUrl, {
    fetch: { users: { ana_f309: { status: 200, data: makeProfile({ is_public: false }) } } },
  });
  assert.equal(hidden.statusCode, 404);
  assert.ok(!hidden.body.includes('Ana'));
  assert.ok(!hidden.body.includes('Cinéfila'));
});

test('handler_falls_back_to_generic_meta_when_list_or_profile_api_fails', async () => {
  const cases = [
    { url: listUrl, fetch: { lists: { [SLUG]: { status: 500, data: null } } }, env: ENV, expectsApiCall: true },
    { url: listUrl, fetch: { lists: { [SLUG]: 'throw' } }, env: ENV, expectsApiCall: true },
    { url: listUrl, fetch: { lists: okList() }, env: {}, expectsApiCall: false },
    { url: profileUrl, fetch: { users: { ana_f309: { status: 500, data: null } } }, env: ENV, expectsApiCall: true },
    { url: profileUrl, fetch: { users: { ana_f309: 'throw' } }, env: ENV, expectsApiCall: true },
    { url: profileUrl, fetch: { users: { ana_f309: { status: 200, data: makeProfile() } } }, env: {}, expectsApiCall: false },
  ];

  for (const { url, fetch, env, expectsApiCall } of cases) {
    const { res, fetchImpl } = await run(url, { fetch, env });

    assert.equal(res.statusCode, 200, url);
    assert.ok(res.body.includes(DEFAULT_DESCRIPTION));
    assert.equal(res.headers['cache-control'], CACHE_CONTROL_NONE);
    assert.equal(apiCalls(fetchImpl).length > 0, expectsApiCall);
  }
});
