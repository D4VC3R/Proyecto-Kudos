import React from 'react';
import { CATEGORY_ICONS_BY_NAME, CATEGORY_SLUG_ICONS, DEFAULT_CATEGORY_ICON } from '../../lib/constants.js';
import { getInitials, getPlaceholderColors } from '../../lib/placeholder.js';

// Variantes: `card` (tarjetas), `banner` (imagen grande de la ficha/votación) y `thumb` (miniaturas pequeñas).
const VARIANT_STYLES = {
  card: { iconSize: 40, initials: 'text-3xl', gap: 'gap-2' },
  banner: { iconSize: 64, initials: 'text-5xl md:text-6xl', gap: 'gap-3' },
  thumb: { iconSize: 20, initials: 'text-sm', gap: 'gap-0.5' },
};

// Placeholder generativo para los ítems sin imagen: degradado estable a partir del nombre,
// icono de la categoría (category.icon si existe, si no por slug) y hasta dos iniciales.
const ItemPlaceholder = ({ name = '', categorySlug, categoryIcon, className = '', variant = 'card' }) => {
  const styles = VARIANT_STYLES[variant] || VARIANT_STYLES.card;
  const { from, to } = getPlaceholderColors(name);
  const initials = getInitials(name);
  const Icon = (categoryIcon && CATEGORY_ICONS_BY_NAME[categoryIcon])
    || CATEGORY_SLUG_ICONS[categorySlug]
    || DEFAULT_CATEGORY_ICON;

  return (
    <div
      role="img"
      aria-label={name ? `Sin imagen: ${name}` : 'Sin imagen'}
      className={`flex flex-col items-center justify-center text-white select-none ${styles.gap} ${className}`}
      style={{ backgroundImage: `linear-gradient(135deg, ${from}, ${to})` }}
    >
      <Icon size={styles.iconSize} strokeWidth={1.5} aria-hidden="true" />
      {initials && (
        <span aria-hidden="true" className={`font-black leading-none tracking-tight ${styles.initials}`}>
          {initials}
        </span>
      )}
    </div>
  );
};

export default ItemPlaceholder;
