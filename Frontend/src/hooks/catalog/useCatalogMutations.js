import axiosClient from '../../core/axiosClient.js';
import { useBaseMutation } from '../common/useBaseMutation';
import { ITEM_KEYS } from '../items/useItemQueries';
import { CATALOG_KEYS } from './useCatalogQueries';

// Importa un título de la fuente externa (POST /catalog/import, F1-07). 201: título nuevo; 200: ya estaba en Kudos.
export const useImportCatalogItem = () => {
  return useBaseMutation({
    mutationFn: ({ category, externalId }) => axiosClient.post('/catalog/import', { category, external_id: externalId }),
    invalidateKeys: [ITEM_KEYS.all, CATALOG_KEYS.all, ['categories']],
    successMessage: (response) => response?.message,
  });
};
