import { extendTailwindMerge } from 'tailwind-merge';

// `twMerge` que conoce la escala tipográfica propia (`text-small`, `text-caption`…). Con el de serie, esas clases
// se toman por colores de texto y `text-small text-ink` se queda solo con la última.
export const twMerge = extendTailwindMerge({
  extend: {
    classGroups: {
      'font-size': [{ text: ['display', 'h1', 'h2', 'h3', 'body', 'small', 'caption'] }],
    },
  },
});
