/** @type {import('tailwindcss').Config} */
export default {
  darkMode: 'class',
  content: [
    "./index.html",
    "./src/**/*.{js,ts,jsx,tsx}",
  ],
  theme: {
    extend: {
      colors: {
        primary: '#2563eb',
        accent: '#facc15',

        background: 'rgb(var(--color-background) / <alpha-value>)',
        surface: 'rgb(var(--color-surface) / <alpha-value>)',
        border: 'rgb(var(--color-border) / <alpha-value>)',
        'border-strong': 'rgb(var(--color-border-strong) / <alpha-value>)',

        text: {
          highlight: 'rgb(var(--color-text-highlight) / <alpha-value>)',
          normal: 'rgb(var(--color-text-normal) / <alpha-value>)',
          btn: 'rgb(var(--color-text-btn) / <alpha-value>)',
          subtitle: 'rgb(var(--color-text-subtitle) / <alpha-value>)',
        },

        nav: {
          item: 'rgb(var(--color-nav-item) / <alpha-value>)',
          'hover-bg': 'rgb(var(--color-nav-hover-bg) / <alpha-value>)',
          'hover-text': 'rgb(var(--color-nav-hover-text) / <alpha-value>)',
        },

        logo: {
          'star-hi': '#FFE082',
          'star-lt': '#facc15',
          'star-md': '#FFA000',
          'star-sh': '#FF6F00',
          'star-dk': '#facc15',
          'chart-top': '#4FC3F7',
          'chart-bot': '#2563eb',
          'orbit-color': '#2563eb',
        },
      },
      fontFamily: {
        sans: ['"Inter Variable"', 'Inter', 'ui-sans-serif', 'system-ui', 'sans-serif'],
      },
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