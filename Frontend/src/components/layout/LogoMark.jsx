import React from 'react';

// Sello de doble filete con la estrella dorada dentro. El trazo usa `currentColor`, así que sigue al color del texto.
const LogoMark = ({ size = 30, className }) => (
  <svg width={size} height={size} viewBox="0 0 32 32" aria-hidden="true" focusable="false" className={className}>
    <circle cx="16" cy="16" r="14.75" fill="none" stroke="currentColor" strokeWidth="1.25" />
    <circle cx="16" cy="16" r="12.25" fill="none" stroke="currentColor" strokeWidth=".75" />
    <path
      className="fill-kudos"
      d="M16 7.2l2.5 5.1 5.6.8-4.05 3.95.95 5.55L16 19.95l-5 2.65.95-5.55-4.05-3.95 5.6-.8z"
      stroke="currentColor"
      strokeWidth="1"
      strokeLinejoin="round"
    />
  </svg>
);

export default LogoMark;
