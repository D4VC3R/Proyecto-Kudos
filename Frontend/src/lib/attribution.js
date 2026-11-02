import { hasText } from './text.js';

// Indica si un ítem trae alguna atribución que mostrar (attribution de ItemDetailResource, F1-06).
export const hasAttribution = (attribution) => Boolean(attribution?.data_source || attribution?.description_source);

const COMMONS_HOST = 'commons.wikimedia.org';

// Indica si una URL es una página de Wikimedia Commons.
const isCommonsUrl = (url) => {
  if (!hasText(url)) return false;
  try {
    return new URL(url).hostname === COMMONS_HOST;
  } catch {
    return false;
  }
};

// Crédito de una foto de Wikimedia Commons con autor y licencia (images[] de ItemDetailResource, F4-04), o null.
// Las portadas de TMDB e IGDB no llevan autor ni enlazan a Commons, así que no tienen pie.
export const getCommonsImageCredit = (image) => {
  if (!hasText(image?.author) || !hasText(image?.license) || !isCommonsUrl(image?.source_url)) return null;

  return {
    author: image.author.trim(),
    license: image.license.trim(),
    licenseUrl: hasText(image.license_url) ? image.license_url : null,
    sourceUrl: image.source_url,
  };
};
