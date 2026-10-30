// Indica si un ítem trae alguna atribución que mostrar (attribution de ItemDetailResource, F1-06).
export const hasAttribution = (attribution) => Boolean(attribution?.data_source || attribution?.description_source);
