import { useLegalInfo } from '../legal/useLegalQueries';

/**
 * Hook de las páginas legales (/privacidad y /aviso-legal, F3-07).
 *
 * @returns {object} `state` con `contactEmail` (string o null) e `isLoading`; sin acciones.
 */
export const useLegalPage = () => {
  const { data, isLoading } = useLegalInfo();

  return {
    state: {
      contactEmail: data?.contact_email || null,
      isLoading,
    },
    actions: {},
  };
};
