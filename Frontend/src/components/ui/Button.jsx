import React from 'react';
import clsx from 'clsx';
import { twMerge } from '../../lib/twMerge.js';
import { Loader2 } from 'lucide-react';
import {VARIANTS, SIZES, RADII} from '../../lib/buttonStyles.js';

const BASE = 'inline-flex items-center justify-center gap-2 rounded-sm border border-transparent font-semibold leading-tight transition-colors';

// Foco común de todos los controles: anillo del color de acento con separación.
const FOCUS = 'focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-offset-2';

const DISABLED = 'disabled:cursor-not-allowed disabled:opacity-50 aria-disabled:pointer-events-none aria-disabled:opacity-50';

const NEW_VARIANTS = {
  primary: 'bg-accent text-on-accent hover:bg-accent/90',
  secondary: 'border-line-strong bg-surface text-ink hover:border-ink aria-pressed:border-ink aria-pressed:bg-line/50',
  ghost: 'text-ink hover:bg-line/60 aria-pressed:bg-line/50',
  // No hay token `on-danger`: el texto usa el fondo de la página.
  danger: 'bg-danger text-canvas hover:bg-danger/90',
  link: 'text-accent underline decoration-1 underline-offset-4 hover:decoration-2',
};

const NEW_SIZES = {
  md: 'min-h-11 px-4 text-small',
  lg: 'min-h-14 px-5 text-body',
};

// API anterior (`color`, `solid`, `soft`, `outline`, `tab`, `ring` y tamaños `sm`/`icon*`): se mantiene hasta que
// cada página se rediseñe con las variantes nuevas.
const LEGACY_VARIANTS = ['solid', 'soft', 'outline', 'tab', 'ring'];

const Button = React.forwardRef(({
                         as: Component = 'button',
                         children,
                         variant = 'primary',  // primary, secondary, ghost, danger, link (y las anteriores: solid, soft, outline, tab, ring)
                         size = 'md',          // md, lg (y las anteriores: sm, iconSm, iconMd, iconLg)
                         color,                // solo API anterior: primary, danger, neutral, etc.
                         radius,               // solo API anterior: md, lg, xl, full
                         isLoading = false,    // Encapsulamos la lógica del spinner
                         isFullWidth = false,  // Para los botones de formularios
                         className = '',
                         type = 'button',
                         disabled,
                         icon: Icon,
                         ...props
                       }, ref) => {
  const isLegacy = color !== undefined || LEGACY_VARIANTS.includes(variant) || !NEW_SIZES[size];
  const isDisabled = disabled || isLoading;
  const isButton = Component === 'button';

  let styles;
  if (isLegacy) {
    const legacyVariant = variant === 'primary' ? 'solid' : variant;
    const variantStyles = VARIANTS[legacyVariant]?.[color ?? 'primary'] || VARIANTS.solid.primary;
    const sizeStyles = SIZES[size] || SIZES.md;
    const radiusStyles = RADII[radius] || RADII.xl;
    styles = clsx('font-bold', variantStyles, sizeStyles, radiusStyles);
  } else {
    const variantStyles = NEW_VARIANTS[variant] || NEW_VARIANTS.primary;
    const sizeStyles = variant === 'link' ? '' : NEW_SIZES[size];
    styles = clsx(variantStyles, sizeStyles);
  }

  const elementProps = isButton
    ? {type, disabled: isDisabled}
    : isDisabled
      ? {'aria-disabled': true, tabIndex: -1}
      : {};

  return (
    <Component
      ref={ref}
      className={twMerge(clsx(
        isLegacy ? 'inline-flex items-center justify-center gap-2 transition-colors' : BASE,
        FOCUS,
        DISABLED,
        styles,
        isFullWidth && 'w-full',
        className,
      ))}
      {...elementProps}
      {...props}
    >
      {isLoading && <Loader2 size={18} className="animate-spin shrink-0" />}
      {!isLoading && Icon && <Icon size={18} className="shrink-0" />}
      {children}
    </Component>
  );
});

Button.displayName = 'Button';

export default Button;
