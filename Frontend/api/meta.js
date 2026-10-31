import { createMetaHandler } from './_lib/seo.js';

/**
 * Vercel Function (F2-10): devuelve el index.html compilado con <title>, description, og:*, twitter:* y JSON-LD
 * de la ficha /:categorySlug/item/:itemId. vercel.json solo reescribe aquí las peticiones de rastreadores;
 * los navegadores siguen recibiendo la SPA. Lee VITE_API_URL y VITE_STORAGE_URL del entorno de Vercel.
 */
export default createMetaHandler();
