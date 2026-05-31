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

        background: 'var(--color-background)',
        surface: 'var(--color-surface)',
        border: 'var(--color-border)',

        text: {
          highlight: 'var(--color-text-highlight)',
          normal: 'var(--color-text-normal)',
          btn: 'var(--color-text-btn)',
          subtitle: 'var(--color-text-subtitle)',
        },

        nav: {
          item: 'var(--color-nav-item)',
          'hover-bg': 'var(--color-nav-hover-bg)',
          'hover-text': 'var(--color-nav-hover-text)',
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
        sans: ['Inter', 'ui-sans-serif', 'system-ui', 'sans-serif'],
      },
    },
  },
  plugins: [],
}