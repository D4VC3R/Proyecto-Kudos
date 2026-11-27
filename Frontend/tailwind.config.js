const rgb = (v) => `rgb(var(--color-${v}) / <alpha-value>)`;

/** @type {import('tailwindcss').Config} */
export default {
  darkMode: 'class',
  content: [
    "./index.html",
    "./src/**/*.{js,ts,jsx,tsx}",
  ],
  theme: {
    // Ancho máximo y márgenes laterales de la maqueta (`--max` y `--gutter`).
    container: {
      center: true,
      padding: { DEFAULT: '1rem', md: '1.5rem' },
      screens: { xl: '1200px' },
    },
    extend: {
      colors: {
        canvas: rgb('canvas'),
        surface: rgb('surface'),
        ink: rgb('ink'),
        'ink-muted': rgb('ink-muted'),
        line: rgb('line'),
        'line-strong': rgb('line-strong'),
        accent: rgb('accent'),
        'on-accent': rgb('on-accent'),
        success: rgb('success'),
        warning: rgb('warning'),
        danger: rgb('danger'),
        info: rgb('info'),
        kudos: rgb('kudos'),
        'on-kudos': rgb('on-kudos'),
        stamp: rgb('stamp'),
        rule: rgb('rule'),
        kicker: rgb('kicker'),
        'cat-icon': rgb('cat-icon'),
        band: rgb('band'),
        'band-ink': rgb('band-ink'),
        'band-muted': rgb('band-muted'),
        'band-line': rgb('band-line'),
        'band-accent': rgb('band-accent'),

        // Alias de los tokens anteriores; desaparecen al migrar las páginas.
        primary: rgb('accent'),
        background: rgb('canvas'),
        border: rgb('line'),
        'border-strong': rgb('line-strong'),

        text: {
          highlight: rgb('ink'),
          normal: rgb('ink-muted'),
          btn: rgb('on-accent'),
          subtitle: rgb('band-muted'),
        },

        nav: {
          item: rgb('ink-muted'),
          'hover-bg': rgb('line'),
          'hover-text': rgb('ink'),
        },
      },
      fontFamily: {
        sans: ['"Inter Variable"', 'Inter', 'ui-sans-serif', 'system-ui', 'sans-serif'],
        display: ['"Fraunces Variable"', 'Fraunces', 'Georgia', 'serif'],
      },
      fontSize: {
        display: ['clamp(2.25rem, 6vw, 4.25rem)', { lineHeight: '1.02', letterSpacing: '-0.025em', fontWeight: '600' }],
        h1: ['clamp(2rem, 4.5vw, 3.25rem)', { lineHeight: '1.04', letterSpacing: '-0.02em', fontWeight: '600' }],
        h2: ['clamp(1.5rem, 3vw, 2rem)', { lineHeight: '1.1', letterSpacing: '-0.015em', fontWeight: '600' }],
        h3: ['1.0625rem', { lineHeight: '1.3', fontWeight: '600' }],
        body: ['1rem', { lineHeight: '1.5' }],
        small: ['0.875rem', { lineHeight: '1.45' }],
        // 12 px es el mínimo legible.
        caption: ['0.75rem', { lineHeight: '1.3' }],
      },
      borderRadius: {
        sm: '6px',
        md: '8px',
        lg: '12px',
        full: '9999px',
      },
      // Sin llamarlas `accent` ni `kudos`: chocarían con las utilidades de color de sombra.
      boxShadow: {
        raised: '0 16px 32px -16px rgb(var(--color-ink) / 0.35)',
        glow: '0 16px 24px -16px rgb(var(--color-accent) / 0.7)',
      },
      // El foco es `focus-visible:ring-2 focus-visible:ring-offset-2`, con ≥ 3:1 en todas las paletas.
      // Los valores por defecto no llevan `<alpha-value>`: Tailwind los vuelca tal cual en las variables base del anillo.
      ringColor: { DEFAULT: 'rgb(var(--color-accent) / 1)' },
      ringOffsetColor: { DEFAULT: 'rgb(var(--color-canvas))' },
      ringOpacity: { DEFAULT: '1' },
      // Animación de entrada de las páginas (antes `animate-in fade-in` de tailwindcss-animate, que no está instalado).
      keyframes: {
        fadeIn: {
          from: { opacity: '0' },
          to: { opacity: '1' },
        },
      },
      animation: {
        'fade-in': 'fadeIn 0.5s ease-out',
      },
      // Alto del contenido a pantalla completa: 100dvh menos la cabecera (80px + borde ≈ 5rem) y el py-8 del <main> (4rem).
      height: {
        'page-content': 'calc(100dvh - 9rem)',
      },
      // Alto mínimo de los estados de carga, vacío o error.
      minHeight: {
        state: '60vh',
      },
    },
  },
  plugins: [],
}
