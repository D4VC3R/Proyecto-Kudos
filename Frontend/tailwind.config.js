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
      // `bump` hace saltar el contador de Kudos al cambiar y `stamp` estampa el sello de la nota en la ficha: son CSS
      // para que el CSS global de `prefers-reduced-motion` las frene sin código. `rise` hace subir las tarjetas en
      // cascada (el retraso sale de `--i`) y también es CSS por lo mismo. `grow` hace crecer las barras del reparto de
      // notas de la ficha, también en CSS para que la regla de `prefers-reduced-motion` la frene. `fly` hace subir y
      // desvanecerse el «+N» del contador de Kudos y `draw` traza la marca de «¡Hecho!» al votar, CSS por lo mismo.
      keyframes: {
        fadeIn: {
          from: { opacity: '0' },
          to: { opacity: '1' },
        },
        bump: {
          '30%': { transform: 'scale(1.25) rotate(-6deg)' },
          '100%': { transform: 'none' },
        },
        stamp: {
          '0%': { opacity: '0', transform: 'scale(1.9) rotate(-20deg)' },
          '55%': { opacity: '1' },
          '100%': { opacity: '1', transform: 'rotate(-3deg)' },
        },
        rise: {
          from: { opacity: '0', transform: 'translateY(14px)' },
        },
        grow: { from: { transform: 'scaleX(0)' } },
        fly: {
          '0%': { opacity: '0', transform: 'translate(-50%, 18px) scale(.7)' },
          '20%': { opacity: '1', transform: 'translate(-50%, 0) scale(1)' },
          '100%': { opacity: '0', transform: 'translate(-50%, -34px)' },
        },
        draw: {
          from: { strokeDasharray: '24', strokeDashoffset: '24' },
          to: { strokeDasharray: '24', strokeDashoffset: '0' },
        },
      },
      animation: {
        'fade-in': 'fadeIn 0.5s ease-out',
        bump: 'bump 0.6s cubic-bezier(.3, 1.6, .5, 1)',
        stamp: 'stamp 0.55s cubic-bezier(.2, 1.5, .4, 1) 0.25s both',
        rise: 'rise 0.45s cubic-bezier(.2, .7, .2, 1) calc(var(--i, 0) * 45ms) both',
        grow: 'grow 0.7s cubic-bezier(.2, .7, .2, 1) 0.35s both',
        fly: 'fly 1.1s cubic-bezier(.2, .7, .2, 1) forwards',
        draw: 'draw 0.45s ease-out 0.1s both',
      },
      // Proporción del póster de una tarjeta.
      aspectRatio: {
        poster: '2 / 3',
      },
      // Ancho de las columnas de las filas horizontales: 2,3 tarjetas en móvil, 4,3 en tableta y 6 en escritorio, como la
      // maqueta (la fracción sobrante insinúa que la fila se desplaza).
      gridAutoColumns: {
        row: 'calc((100% - 24px) / 2.3)',
        'row-md': 'calc((100% - 3 * 16px) / 4.3)',
        'row-lg': 'calc((100% - 5 * 16px) / 6)',
      },
      // Texto y podio de la banda del ranking.
      gridTemplateColumns: {
        band: 'minmax(0, 1fr) minmax(0, 1.1fr)',
        // Póster y texto de la ficha: el póster ocupa hasta 360 px y el resto es del texto.
        detail: 'minmax(0, 360px) minmax(0, 1fr)',
        // Fila del reparto de notas de la ficha: etiqueta del nivel, barra y recuento (hasta cinco cifras).
        dist: '5.5rem minmax(0, 1fr) 2.5rem',
        // Póster y texto de votar: el póster ocupa hasta 280 px (340 px en escritorio) y el resto es de la pregunta.
        vote: 'minmax(0, 280px) minmax(0, 1fr)',
        'vote-lg': 'minmax(0, 340px) minmax(0, 1fr)',
      },
      // Distancia a la que se queda fijo el póster de la ficha al desplazarla: la cabecera (64 px) más 24 px de aire.
      inset: {
        'sticky-offset': '5.5rem',
      },
      transitionTimingFunction: {
        soft: 'cubic-bezier(.2, .7, .2, 1)',
      },
      transitionDuration: {
        250: '250ms',
      },
      textUnderlineOffset: {
        3: '3px',
      },
      // Ancho máximo de las páginas: lectura (las páginas legales, el antiguo `max-w-3xl`), contenido (el `--max` de la
      // maqueta) y panel (la administración, el ancho que tenía todo el sitio).
      maxWidth: {
        reading: '48rem',
        content: '75rem',
        panel: '80rem',
      },
      // Alto máximo de un panel que se abre bajo la cabecera (64 px): así no se sale de la pantalla y su final se ve al desplazarlo.
      maxHeight: {
        'below-header': 'calc(100dvh - 4rem)',
      },
      // Alto mínimo de los estados de carga, vacío o error.
      minHeight: {
        state: '60vh',
      },
    },
  },
  plugins: [],
}
