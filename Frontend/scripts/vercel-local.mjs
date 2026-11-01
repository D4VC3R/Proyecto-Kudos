/**
 * Emula en local el enrutado de Vercel para probar la F2-10 y la F3-09 sin la CLI de Vercel (que exige una cuenta):
 * 1) `/api/meta` → función, 2) archivos de `dist/`, 3) `rewrites` de `vercel.json` en orden, con `has` de cabeceras.
 * Solo admite los patrones que usa `vercel.json` (`:param` y `(.*)`). Uso en `docs/infra/entorno-local.md` §5.
 */
import http from 'node:http';
import path from 'node:path';
import { readFile, stat } from 'node:fs/promises';
import { fileURLToPath, pathToFileURL } from 'node:url';

/** Convierte el `source` de una reescritura (`:param`, `(.*)`) en una expresión regular anclada. */
export function compileSource(source) {
  return new RegExp(`^${source.replace(/:([A-Za-z_]\w*)/g, '(?<$1>[^/]+)')}$`);
}

/** Comprueba las condiciones `has` de una reescritura contra las cabeceras de la petición. */
export function matchesHas(has = [], headers = {}) {
  return has.every((item) => {
    if (item.type !== 'header') return false;
    const raw = headers[item.key.toLowerCase()];
    const value = Array.isArray(raw) ? raw.join(', ') : raw;
    if (value === undefined || value === null) return false;
    if (!item.value) return true;
    return new RegExp(`^${item.value}$`).test(value);
  });
}

/** Devuelve el destino de la primera reescritura que encaja, o `null` si ninguna. */
export function resolveRewrite(pathname, headers, rewrites = []) {
  for (const rule of rewrites) {
    const match = compileSource(rule.source).exec(pathname);
    if (match && matchesHas(rule.has, headers)) {
      return rule.destination.replace(
        /:([A-Za-z_]\w*)/g,
        (_, name) => encodeURIComponent(match.groups?.[name] ?? ''),
      );
    }
  }
  return null;
}

if (process.argv[1] && import.meta.url === pathToFileURL(process.argv[1]).href) {
  const ROOT = path.resolve(path.dirname(fileURLToPath(import.meta.url)), '..');
  const DIST = path.join(ROOT, 'dist');
  const PORT = Number(process.env.PORT ?? 4175);

  const config = JSON.parse(await readFile(path.join(ROOT, 'vercel.json'), 'utf8'));
  const { default: metaHandler } = await import('../api/meta.js');

  const MIME = {
    '.html': 'text/html; charset=utf-8',
    '.js': 'text/javascript; charset=utf-8',
    '.css': 'text/css; charset=utf-8',
    '.svg': 'image/svg+xml',
    '.ico': 'image/x-icon',
    '.png': 'image/png',
    '.webp': 'image/webp',
    '.json': 'application/json; charset=utf-8',
  };

  const serveFile = async (res, filePath) => {
    const body = await readFile(filePath);
    res.statusCode = 200;
    res.setHeader('Content-Type', MIME[path.extname(filePath)] ?? 'application/octet-stream');
    res.end(body);
  };

  http.createServer(async (req, res) => {
    try {
      const pathname = decodeURIComponent(new URL(req.url, 'http://localhost').pathname);

      if (pathname === '/api/meta') {
        await metaHandler(req, res);
        return;
      }

      const filePath = path.join(DIST, pathname);
      if (filePath.startsWith(DIST + path.sep)) {
        let isFile = false;
        try {
          isFile = (await stat(filePath)).isFile();
        } catch {
          // No existe: se prueban las reescrituras.
        }
        if (isFile) {
          await serveFile(res, filePath);
          return;
        }
      }

      const destination = resolveRewrite(pathname, req.headers, config.rewrites);
      if (destination === null) {
        res.statusCode = 404;
        res.end('Not found');
      } else if (destination.startsWith('/api/meta')) {
        req.url = destination;
        await metaHandler(req, res);
      } else {
        await serveFile(res, path.join(DIST, new URL(destination, 'http://localhost').pathname));
      }
    } catch (error) {
      console.error(error);
      res.statusCode = 500;
      res.end('Internal error');
    }
  }).listen(PORT, () => {
    console.log(`Emulación local de Vercel en http://localhost:${PORT}`);
  });
}
