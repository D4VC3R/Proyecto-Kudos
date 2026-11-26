/**
 * Comprueba que cada clase de Tailwind escrita en `src/` existe en el CSS compilado (`dist/assets/*.css`).
 * Tailwind ignora en silencio las clases que no puede generar (p. ej. `bg-surface/60` con un color sin canales RGB),
 * así que este script las saca a la luz. Uso: `pnpm build && pnpm check:classes`.
 * Es una heurística: extrae los literales de cadena del código, descarta los comentarios y se queda con las palabras
 * que empiezan por un prefijo de utilidad de Tailwind. Sale con código 1 si falta alguna clase.
 */
import { readdir, readFile } from 'node:fs/promises';
import path from 'node:path';
import { fileURLToPath } from 'node:url';

const root = path.resolve(path.dirname(fileURLToPath(import.meta.url)), '..');
const srcDir = path.join(root, 'src');
const cssDir = path.join(root, 'dist', 'assets');

// Palabras que no son clases (valores de estilo en línea, tipos de input…) o marcadores que Tailwind no emite
// (`group`, `peer`). Nunca se añade aquí una clase real que falte en el CSS: se define o se quita del código.
const IGNORE = new Set([
  'group',
  'peer',
  'bottom-center', // posición de react-hot-toast
  'list-visibility', // id de un campo de formulario
]);

// Prefijos de utilidad (la clase base debe ser `prefijo-algo`).
const PREFIXES = [
  'bg', 'text', 'border', 'ring', 'outline', 'divide', 'w', 'h', 'size', 'min', 'max', 'gap', 'space', 'rounded', 'shadow', 'font', 'animate',
  'animation', 'opacity', 'flex', 'grid', 'col', 'row', 'items', 'justify', 'content', 'self', 'place', 'inset', 'top',
  'left', 'right', 'bottom', 'z', 'translate', 'scale', 'rotate', 'blur', 'brightness', 'from', 'via', 'to', 'leading',
  'tracking', 'overflow', 'object', 'aspect', 'mix', 'drop', 'backdrop', 'duration', 'delay', 'ease', 'transition',
  'cursor', 'select', 'pointer', 'fill', 'stroke', 'line', 'whitespace', 'break', 'underline', 'decoration', 'order',
  'basis', 'grow', 'shrink', 'origin', 'sr', 'list', 'align', 'resize', 'accent', 'caret', 'scroll', 'snap', 'touch',
  'will', 'columns', 'float', 'clear', 'isolation', 'object', 'table', 'indent', 'placeholder', 'file', 'ps', 'pe',
];
const PREFIX_RE = new RegExp(`^(?:${PREFIXES.join('|')})-.`);
// Margen y relleno: el valor es un número, `auto`, `px` o un valor arbitrario (así `my-lists` no cuenta como clase).
const SPACING_RE = /^-?[pm][trblxyse]?-(?:\d|\[|auto$|px$)/;

// Utilidades de una sola palabra, para no confundirlas con texto (`'text'`, `'row'`…).
const STANDALONE = new Set([
  'flex', 'grid', 'block', 'inline', 'hidden', 'contents', 'border', 'rounded', 'shadow', 'transition', 'truncate',
  'uppercase', 'lowercase', 'capitalize', 'italic', 'underline', 'relative', 'absolute', 'fixed', 'sticky', 'static',
  'container', 'blur', 'ring', 'outline', 'invisible', 'visible', 'grow', 'shrink', 'resize', 'antialiased',
]);

async function walk(dir) {
  const files = [];
  for (const entry of await readdir(dir, { withFileTypes: true })) {
    const full = path.join(dir, entry.name);
    if (entry.isDirectory()) files.push(...(await walk(full)));
    else if (/\.(js|jsx)$/.test(entry.name)) files.push(full);
  }
  return files;
}

/**
 * Recorre el código y devuelve los literales de cadena (`'…'`, `"…"` y los fragmentos estáticos de `` `…` ``)
 * ignorando los comentarios (`// …` al principio de línea o tras un espacio, y los de bloque).
 */
export function extractStrings(src) {
  const strings = [];
  const braceStack = []; // por cada `${` abierto, cuántas llaves anidadas lleva
  let i = 0;

  const readTemplate = () => {
    let buf = '';
    while (i < src.length) {
      const ch = src[i];
      if (ch === '\\') { buf += ' '; i += 2; continue; }
      if (ch === '`') { strings.push(buf); i++; return; }
      if (ch === '$' && src[i + 1] === '{') {
        strings.push(buf);
        braceStack.push(0);
        i += 2;
        return;
      }
      buf += ch;
      i++;
    }
    strings.push(buf);
  };

  while (i < src.length) {
    const ch = src[i];
    const next = src[i + 1];

    if (ch === '/' && next === '*') {
      const end = src.indexOf('*/', i + 2);
      i = end === -1 ? src.length : end + 2;
    } else if (ch === '/' && next === '/' && (i === 0 || /\s/.test(src[i - 1]))) {
      const end = src.indexOf('\n', i);
      i = end === -1 ? src.length : end;
    } else if (ch === "'" || ch === '"') {
      // Una cadena simple no cruza líneas: si no cierra, era un apóstrofo de texto JSX.
      let j = i + 1;
      let buf = '';
      while (j < src.length && src[j] !== ch && src[j] !== '\n') {
        if (src[j] === '\\') { buf += ' '; j += 2; continue; }
        buf += src[j];
        j++;
      }
      if (src[j] === ch) { strings.push(buf); i = j + 1; } else { i++; }
    } else if (ch === '`') {
      i++;
      readTemplate();
    } else if (ch === '{' && braceStack.length) {
      braceStack[braceStack.length - 1]++;
      i++;
    } else if (ch === '}' && braceStack.length) {
      if (braceStack[braceStack.length - 1] === 0) {
        braceStack.pop();
        i++;
        readTemplate();
      } else {
        braceStack[braceStack.length - 1]--;
        i++;
      }
    } else {
      i++;
    }
  }
  return strings;
}

/** Quita las variantes (`hover:`, `md:`…: todo hasta el último `:` fuera de corchetes) y el `!` o `-` inicial. */
function baseOf(token) {
  let depth = 0;
  let cut = -1;
  for (let k = 0; k < token.length; k++) {
    if (token[k] === '[') depth++;
    else if (token[k] === ']') depth--;
    else if (token[k] === ':' && depth === 0) cut = k;
  }
  return token.slice(cut + 1).replace(/^[!-]/, '');
}

export function isCandidate(token) {
  if (IGNORE.has(token) || !/^[\w\-:/.[\]()%#,!&'=>*@+]+$/.test(token)) return false;
  const base = baseOf(token);
  return PREFIX_RE.test(base) || SPACING_RE.test(base) || STANDALONE.has(base);
}

/** Selector CSS de la clase tal y como lo escribe Tailwind: `\` delante de los caracteres especiales. */
export function classRegex(token) {
  // Barra invertida literal + el carácter, ya escapado para la expresión regular.
  const escape = (c) => (/[\w-]/.test(c) ? c : `\\\\\\${c}`);
  // Un dígito inicial se escribe como `\3N ` en CSS (`2xl:` → `.\32xl\:`).
  const body = /^\d/.test(token)
    ? `\\\\3${token[0]} ?${[...token.slice(1)].map(escape).join('')}`
    : [...token].map(escape).join('');
  return new RegExp(`\\.${body}(?![\\w-]|\\\\)`);
}

async function main() {
  let cssFiles;
  try {
    cssFiles = (await readdir(cssDir)).filter((f) => f.endsWith('.css'));
  } catch {
    cssFiles = [];
  }
  if (!cssFiles.length) {
    console.error('No hay CSS en dist/assets: ejecuta antes `pnpm build`.');
    process.exit(1);
  }
  const css = (await Promise.all(cssFiles.map((f) => readFile(path.join(cssDir, f), 'utf8')))).join('\n');

  const missing = new Map(); // clase → archivos donde aparece
  const seen = new Map(); // clase → está en el CSS
  for (const file of await walk(srcDir)) {
    const src = await readFile(file, 'utf8');
    for (const str of extractStrings(src)) {
      for (const token of str.split(/\s+/).filter(Boolean)) {
        if (!isCandidate(token)) continue;
        if (!seen.has(token)) seen.set(token, classRegex(token).test(css));
        if (seen.get(token)) continue;
        const rel = path.relative(root, file);
        if (!missing.has(token)) missing.set(token, new Set());
        missing.get(token).add(rel);
      }
    }
  }

  if (missing.size) {
    console.error(`Clases de Tailwind sin definir en el CSS compilado (${missing.size}):`);
    for (const [token, files] of [...missing].sort(([a], [b]) => a.localeCompare(b))) {
      console.error(`  ${token}  ←  ${[...files].join(', ')}`);
    }
    process.exit(1);
  }
  console.log(`OK: las ${seen.size} clases de Tailwind de src/ existen en el CSS compilado.`);
}

if (process.argv[1] && path.resolve(process.argv[1]) === fileURLToPath(import.meta.url)) await main();
