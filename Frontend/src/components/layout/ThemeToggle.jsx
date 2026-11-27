import React from 'react';
import { Moon, Sun } from 'lucide-react';
import { twMerge } from '../../lib/twMerge.js';
import { useTheme } from '../../hooks/common/useTheme.js';
import { centerOf, runThemeTransition } from '../../hooks/common/themeTransition.js';
import { ICON_BUTTON } from './headerStyles.js';

// Alterna entre claro y oscuro desde la cabecera; la paleta y el modo "Sistema" se eligen en el menú de usuario.
const ThemeToggle = ({ className }) => {
  const { isDark, setMode } = useTheme();
  const label = isDark ? 'Cambiar a modo claro' : 'Cambiar a modo oscuro';
  const Icon = isDark ? Sun : Moon;

  const handleClick = (e) => {
    const next = isDark ? 'light' : 'dark';
    runThemeTransition('theme', () => setMode(next), centerOf(e.currentTarget));
  };

  return (
    <button type="button" onClick={handleClick} aria-label={label} title={label} className={twMerge(ICON_BUTTON, className)}>
      <Icon size={20} aria-hidden="true" />
    </button>
  );
};

export default ThemeToggle;
