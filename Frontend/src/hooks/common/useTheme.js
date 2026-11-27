import { useSyncExternalStore } from 'react';

export const THEME_STORAGE_KEY = 'kudos.theme';
export const PALETTES = ['azulejo', 'original', 'taquilla', 'videoclub'];
export const MODES = ['light', 'dark', 'system'];

const DEFAULTS = { palette: 'azulejo', mode: 'system' };
const DARK_QUERY = '(prefers-color-scheme: dark)';

/** Sustituye por el valor por defecto todo lo que no sea una paleta o un modo válidos. */
const normalize = (value) => ({
  palette: PALETTES.includes(value?.palette) ? value.palette : DEFAULTS.palette,
  mode: MODES.includes(value?.mode) ? value.mode : DEFAULTS.mode,
});

/** Lee el tema guardado; si no hay nada, está corrupto o el almacenamiento falla, devuelve los valores por defecto. */
const readStored = () => {
  try {
    return normalize(JSON.parse(localStorage.getItem(THEME_STORAGE_KEY)));
  } catch {
    return { ...DEFAULTS };
  }
};

let current = readStored();
const listeners = new Set();

/** Pone `data-palette` y la clase `.dark` en `<html>` y ajusta el `theme-color` del navegador al lienzo resultante. */
const apply = ({ palette, mode }) => {
  const root = document.documentElement;
  const dark = mode === 'dark' || (mode === 'system' && !!window.matchMedia?.(DARK_QUERY).matches);

  root.dataset.palette = palette;
  root.classList.toggle('dark', dark);

  const canvas = getComputedStyle(root).getPropertyValue('--color-canvas').trim();
  const meta = document.querySelector('meta[name="theme-color"]');
  if (canvas && meta) {
    meta.setAttribute('content', `rgb(${canvas})`);
  }
};

const notify = () => listeners.forEach((listener) => listener());

const update = (patch) => {
  current = normalize({ ...current, ...patch });
  try {
    localStorage.setItem(THEME_STORAGE_KEY, JSON.stringify(current));
  } catch {
    // Sin almacenamiento el tema sigue aplicándose en esta sesión, pero no se recuerda.
  }
  apply(current);
  notify();
};

let detach = null;

const subscribe = (listener) => {
  listeners.add(listener);

  if (listeners.size === 1) {
    apply(current);

    // Otra pestaña ha cambiado el tema (o ha vaciado el almacenamiento: `key` es `null`).
    const onStorage = (e) => {
      if (e.key === THEME_STORAGE_KEY || e.key === null) {
        current = readStored();
        apply(current);
        notify();
      }
    };
    // Con el modo `system`, el tema sigue al del sistema operativo.
    const media = window.matchMedia?.(DARK_QUERY);
    const onSystemChange = () => {
      if (current.mode === 'system') {
        apply(current);
      }
    };

    window.addEventListener('storage', onStorage);
    media?.addEventListener('change', onSystemChange);
    detach = () => {
      window.removeEventListener('storage', onStorage);
      media?.removeEventListener('change', onSystemChange);
    };
  }

  return () => {
    listeners.delete(listener);
    if (listeners.size === 0) {
      detach?.();
      detach = null;
    }
  };
};

const getSnapshot = () => current;

/**
 * Tema de la interfaz: paleta y modo (claro, oscuro o el del sistema).
 *
 * El estado vive a nivel de módulo, así que todas las instancias del hook comparten el mismo valor.
 * Se guarda en `localStorage` (`kudos.theme`) y se aplica en `<html>` con `data-palette` y la clase `dark`.
 *
 * @returns `{ palette, mode, setPalette, setMode }`. Los setters ignoran valores que no estén en `PALETTES` o `MODES`.
 */
export const useTheme = () => {
  const { palette, mode } = useSyncExternalStore(subscribe, getSnapshot);

  const setPalette = (value) => {
    if (PALETTES.includes(value)) update({ palette: value });
  };
  const setMode = (value) => {
    if (MODES.includes(value)) update({ mode: value });
  };

  return { palette, mode, setPalette, setMode };
};
