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
        }
      }
    },
  },
  plugins: [],
}