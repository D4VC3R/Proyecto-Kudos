import { useHome } from '../home/useHomeQueries';
import { useUserRanking } from '../users/useUserQueries';
import { selectIsAuthenticated, useSessionStore } from '../../store/useSessionStore.js';

/**
 * Hook para la portada: "Para ti" (o populares sin sesión), banda del ranking, novedades, listas
 * destacadas, mejor valorados y accesos a las categorías.
 *
 * @returns {object} `state` con las secciones ya preparadas y `actions` vacío (las tarjetas son enlaces).
 */
export const useHomePage = () => {
  const isAuthenticated = useSessionStore(selectIsAuthenticated);
  const { data, isLoading, isError, isFetching } = useHome();
  const { data: rankingResponse } = useUserRanking(1);

  const categories = data?.categories ?? [];
  const newReleases = data?.new_releases ?? [];
  const editorialLists = data?.editorial_lists ?? [];
  const topRated = data?.top_rated ?? [];

  // Una fila por grupo; con "because" se titula "Porque te gustó X".
  const forYouRows = (data?.for_you?.groups ?? [])
    .filter((group) => group.items?.length)
    .map((group, index) => ({
      key: group.because?.id ?? `popular-${index}`,
      because: group.because?.name ?? null,
      items: group.items,
    }));

  // Banda del ranking: el podio y, si el usuario queda fuera, su fila como cuarta. El ranking es global y público;
  // `my_position` solo viene con sesión. El ranking no es imprescindible: si falla, la banda no sale.
  const topPage = rankingResponse?.data?.top_page ?? [];
  const myPageData = rankingResponse?.data?.my_page_data ?? [];
  const myPosition = rankingResponse?.meta?.my_position ?? null;
  const podium = topPage.slice(0, 3).map((user) => ({ ...user, isMe: user.id === myPosition?.user_id }));
  const myRow = myPosition?.rank > 3 ? myPageData.find((user) => user.id === myPosition.user_id) : null;
  const above = myPosition?.rank > 1 ? [...topPage, ...myPageData].find((user) => user.rank === myPosition.rank - 1) : null;
  // Para superar al de arriba hacen falta sus kudos más uno: en un empate gana la cuenta más antigua.
  const kudosToNext = above ? Math.max(1, above.total_kudos - myPosition.total_kudos + 1) : null;

  // Fecha larga del día, el "kicker" del titular; con sesión se presenta como su diario.
  const today = new Intl.DateTimeFormat('es-ES', { weekday: 'long', day: 'numeric', month: 'long' }).format(new Date());

  const isEmpty = !forYouRows.length && !newReleases.length && !editorialLists.length && !topRated.length;

  return {
    state: {
      categories,
      newReleases,
      editorialLists,
      topRated,
      forYouTitle: isAuthenticated ? 'Para ti' : 'Populares',
      forYouRows,
      ranking: {
        podium,
        me: myRow ? { ...myRow, isMe: true } : null,
        myRank: myPosition?.rank ?? null,
        myKudos: myPosition?.total_kudos ?? null,
        kudosToNext,
      },
      isAuthenticated,
      heroKicker: isAuthenticated ? `Tu diario · ${today}` : today,
      isLoading,
      isError,
      isBackgroundUpdating: isFetching && !isLoading,
      isEmpty,
    },
    actions: {},
  };
};
