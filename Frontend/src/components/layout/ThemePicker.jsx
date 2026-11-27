import React, { useId } from 'react';
import { Check, Monitor, Moon, Sun } from 'lucide-react';
import { twMerge } from '../../lib/twMerge.js';
import { useTheme } from '../../hooks/common/useTheme.js';
import { centerOf, runThemeTransition } from '../../hooks/common/themeTransition.js';
import { FOCUS_RING, MENU_ITEM } from './headerStyles.js';

// Acento y lienzo claros de cada paleta, para su muestra. Van aquí y no en el CSS porque son colores de otra
// paleta distinta de la que está activa.
const PALETTE_OPTIONS = [
  { value: 'azulejo', label: 'Azulejo', accent: '#2342C2', canvas: '#F5F7FB' },
  { value: 'original', label: 'Original', accent: '#B93815', canvas: '#FAF7F2' },
  { value: 'taquilla', label: 'Taquilla', accent: '#17694C', canvas: '#F3F6F3' },
  { value: 'videoclub', label: 'Videoclub', accent: '#B0175F', canvas: '#FAF7FA' },
];

const MODE_OPTIONS = [
  { value: 'light', label: 'Claro', icon: Sun },
  { value: 'dark', label: 'Oscuro', icon: Moon },
  { value: 'system', label: 'Sistema', icon: Monitor },
];

const INLINE_OPTION = 'inline-flex min-h-11 items-center gap-2 rounded-sm border border-line px-3 text-small text-ink aria-checked:border-ink';

const Swatch = ({ canvas, accent }) => (
  <span
    aria-hidden="true"
    className="h-4 w-4 shrink-0 rounded-full border border-line-strong"
    style={{ background: `linear-gradient(135deg, ${canvas} 0 50%, ${accent} 50%)` }}
  />
);

// Selector de paleta y de modo. En `menu` son elementos de un menú (`menuitemradio`, que no lo cierran al elegir);
// en `inline` (el pie y el menú móvil), dos grupos de radios con las flechas del teclado.
const ThemePicker = ({ variant = 'inline', className }) => {
  const { palette, mode, setPalette, setMode } = useTheme();
  const titleId = useId();
  const isMenu = variant === 'menu';
  const role = isMenu ? 'menuitemradio' : 'radio';

  const choosePalette = (value) => {
    if (value !== palette) runThemeTransition('palette', () => setPalette(value));
  };
  const chooseMode = (value, e) => {
    if (value !== mode) runThemeTransition('theme', () => setMode(value), centerOf(e.currentTarget));
  };

  // Radios agrupados: ↓/→ pasa al siguiente y ↑/← al anterior (en círculo); Inicio y Fin, a los extremos.
  const handleGroupKeyDown = (e) => {
    const radios = [...e.currentTarget.querySelectorAll('[role="radio"]')];
    const index = radios.indexOf(document.activeElement);
    if (index === -1) return;

    let target;
    if (e.key === 'ArrowRight' || e.key === 'ArrowDown') target = (index + 1) % radios.length;
    else if (e.key === 'ArrowLeft' || e.key === 'ArrowUp') target = (index - 1 + radios.length) % radios.length;
    else if (e.key === 'Home') target = 0;
    else if (e.key === 'End') target = radios.length - 1;
    else return;

    e.preventDefault();
    radios[target].focus();
    radios[target].click();
  };

  const groupProps = (name) => ({
    ...(isMenu ? { role: 'group' } : { role: 'radiogroup', onKeyDown: handleGroupKeyDown }),
    ...(name ? { 'aria-label': name } : { 'aria-labelledby': titleId }),
  });

  const optionProps = (checked) => ({
    type: 'button',
    role,
    'aria-checked': checked,
    // En el menú el foco lo mueven las flechas del menú; en los radios, solo el activo entra por Tab.
    tabIndex: !isMenu && checked ? 0 : -1,
  });

  const title = (
    <p
      id={titleId}
      role={isMenu ? 'presentation' : undefined}
      className={twMerge('text-caption font-semibold uppercase tracking-wider text-kicker', isMenu && 'px-2.5 pb-1 pt-2')}
    >
      Cambiar tema
    </p>
  );

  return (
    <div className={twMerge(isMenu ? 'grid gap-0.5' : 'grid gap-3', className)}>
      {title}

      <div {...groupProps()} className={isMenu ? 'grid gap-0.5' : 'flex flex-wrap gap-2'}>
        {PALETTE_OPTIONS.map(({ value, label, accent, canvas }) => {
          const checked = palette === value;
          return (
            <button
              key={value}
              {...optionProps(checked)}
              onClick={() => choosePalette(value)}
              className={isMenu ? MENU_ITEM : twMerge(INLINE_OPTION, FOCUS_RING)}
            >
              <Swatch canvas={canvas} accent={accent} />
              <span>{label}</span>
              {isMenu && checked && <Check size={16} aria-hidden="true" className="ml-auto" />}
            </button>
          );
        })}
      </div>

      <div
        {...groupProps('Modo')}
        className={isMenu ? 'grid grid-cols-3 gap-0.5' : 'flex flex-wrap gap-2'}
      >
        {MODE_OPTIONS.map(({ value, label, icon: Icon }) => (
          <button
            key={value}
            {...optionProps(mode === value)}
            onClick={(e) => chooseMode(value, e)}
            className={isMenu
              ? twMerge(MENU_ITEM, 'flex-col justify-center gap-1 border border-transparent px-1 text-center text-caption aria-checked:border-ink')
              : twMerge(INLINE_OPTION, FOCUS_RING)}
          >
            <Icon size={16} aria-hidden="true" />
            <span>{label}</span>
          </button>
        ))}
      </div>
    </div>
  );
};

export default ThemePicker;
