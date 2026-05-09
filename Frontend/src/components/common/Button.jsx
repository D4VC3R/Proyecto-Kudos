import React from 'react';
import { Loader2 } from 'lucide-react'; // Asumo que usas lucide-react por tus iconos
import {VARIANTS, SIZES, RADII} from './../../lib/buttonStyles.js';

export const Button = ({
                         children,
                         variant = 'solid',   // solid, soft, ghost, outline
                         color = 'primary',   // primary, danger, neutral, neutralToPrimary, etc.
                         size = 'md',         // sm, md, lg, iconSm, iconMd, iconLg
                         radius = 'xl',       // md, lg, xl, full
                         isLoading = false,   // Encapsulamos la lógica del spinner
                         isFullWidth = false, // Para los botones de formularios
                         className = '',
                         type = 'button',
                         disabled,
                         icon: Icon,
                         ...props
                       }) => {
  const baseStyles = 'inline-flex items-center justify-center gap-2 font-bold transition-colors focus:outline-none focus:ring-2 focus:ring-blue-200 disabled:opacity-50 disabled:cursor-not-allowed disabled:pointer-events-none';

  const variantStyles = VARIANTS[variant]?.[color] || VARIANTS.solid.primary;
  const sizeStyles = SIZES[size] || SIZES.md;
  const radiusStyles = RADII[radius] || RADII.xl;
  const widthStyles = isFullWidth ? 'w-full' : '';
  const isDisabled = disabled || isLoading;

  return (
    <button
      type={type}
      disabled={isDisabled}
      className={`${baseStyles} ${variantStyles} ${sizeStyles} ${radiusStyles} ${widthStyles} ${className}`}
      {...props}
    >
      {isLoading && <Loader2 size={18} className="animate-spin shrink-0" />}
      {!isLoading && Icon && <Icon size={18} className="shrink-0" />}
      {children}
    </button>
  );
};