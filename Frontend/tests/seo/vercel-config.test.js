import { test } from 'node:test';
import assert from 'node:assert/strict';
import { readFileSync } from 'node:fs';
import { compileSource, resolveRewrite } from '../../scripts/vercel-local.mjs';

const ITEM_ID = '01a18c59-f943-7066-a3eb-c6abc586883b';
const config = JSON.parse(readFileSync(new URL('../../vercel.json', import.meta.url)));
const resolve = (pathname, headers) => resolveRewrite(pathname, headers, config.rewrites);
const CRAWLER_AGENTS = [
  'facebookexternalhit/1.1 (+http://www.facebook.com/externalhit_uatext.php)',
  'Twitterbot/1.0',
  'WhatsApp/2.23.20.0',
  'LinkedInBot/1.0 (compatible; Mozilla/5.0; Apache-HttpClient +http://www.linkedin.com)',
  'Slackbot-LinkExpanding 1.0 (+https://api.slack.com/robots)',
  'Mozilla/5.0 (compatible; Discordbot/2.0; +https://discordapp.com)',
  'TelegramBot (like TwitterBot)',
  'Mozilla/5.0 (compatible; Googlebot/2.1; +http://www.google.com/bot.html)',
];

test('vercel_json_keeps_ignore_command_and_spa_fallback', () => {
  assert.equal(config.ignoreCommand, 'git diff --quiet HEAD^ HEAD -- .');
  assert.deepEqual(config.rewrites.at(-1), { source: '/(.*)', destination: '/index.html' });
});

test('crawler_user_agents_are_rewritten_to_meta_function', () => {
  for (const ua of CRAWLER_AGENTS) {
    assert.equal(
      resolve(`/peliculas/item/${ITEM_ID}`, { 'user-agent': ua }),
      `/api/meta?categorySlug=peliculas&itemId=${ITEM_ID}`,
      ua,
    );
  }
});

test('browser_user_agents_get_the_spa', () => {
  const agents = [
    'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/130.0 Safari/537.36',
    'Mozilla/5.0 (X11; Linux x86_64; rv:131.0) Gecko/20100101 Firefox/131.0',
    'Mozilla/5.0 (iPhone; CPU iPhone OS 17_5 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/17.5 Mobile/15E148 Safari/604.1',
  ];

  for (const ua of agents) {
    assert.equal(resolve(`/peliculas/item/${ITEM_ID}`, { 'user-agent': ua }), '/index.html', ua);
  }
  assert.equal(resolve(`/peliculas/item/${ITEM_ID}`, {}), '/index.html');
});

test('other_routes_get_the_spa_for_crawlers_too', () => {
  const headers = { 'user-agent': 'facebookexternalhit/1.1' };
  for (const pathname of ['/peliculas', '/listas', '/u', '/mis-listas/abc', '/listas/mi-lista/extra', '/u/ana/extra', '/peliculas/item/x/extra']) {
    assert.equal(resolve(pathname, headers), '/index.html', pathname);
  }
});

test('crawler_user_agents_are_rewritten_for_lists_and_profiles', () => {
  for (const ua of CRAWLER_AGENTS) {
    assert.equal(resolve('/listas/mi-lista', { 'user-agent': ua }), '/api/meta?type=list&slug=mi-lista', ua);
    assert.equal(resolve('/u/ana_f309', { 'user-agent': ua }), '/api/meta?type=profile&username=ana_f309', ua);
  }
});

test('browser_user_agents_get_the_spa_on_lists_and_profiles', () => {
  const agents = [
    'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/130.0 Safari/537.36',
    'Mozilla/5.0 (X11; Linux x86_64; rv:131.0) Gecko/20100101 Firefox/131.0',
    'Mozilla/5.0 (iPhone; CPU iPhone OS 17_5 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/17.5 Mobile/15E148 Safari/604.1',
  ];

  for (const pathname of ['/listas/mi-lista', '/u/ana_f309']) {
    for (const ua of agents) {
      assert.equal(resolve(pathname, { 'user-agent': ua }), '/index.html', `${pathname} ${ua}`);
    }
    assert.equal(resolve(pathname, {}), '/index.html', pathname);
  }
});

test('crawler_rules_share_the_same_user_agent_pattern', () => {
  const crawlerRules = config.rewrites.filter((rule) => rule.has);
  assert.deepEqual(
    crawlerRules.map((rule) => rule.source),
    ['/:categorySlug/item/:itemId', '/listas/:slug', '/u/:username'],
  );
  for (const rule of crawlerRules) {
    assert.deepEqual(rule.has, crawlerRules[0].has);
  }

  const catchAll = config.rewrites.findIndex((rule) => rule.source === '/(.*)');
  assert.equal(catchAll, config.rewrites.length - 1);
  for (const rule of crawlerRules) {
    assert.ok(config.rewrites.indexOf(rule) < catchAll);
  }
});

test('compile_source_extracts_named_params', () => {
  const { groups } = compileSource('/:categorySlug/item/:itemId').exec('/series/item/abc');
  assert.deepEqual({ ...groups }, { categorySlug: 'series', itemId: 'abc' });
});
