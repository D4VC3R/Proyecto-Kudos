import { useUserRankingContext } from '../../hooks/useUserRankingContext';

export const UserRankingTable = () => {
  const { rows, myPosition, isLoading, isError } = useUserRankingContext();

  const showLoadingRow = isLoading;
  const showErrorRow = isError;
  const showEmptyRow = !isLoading && !isError && rows.length === 0;
  const showDataRows = !isLoading && !isError && rows.length > 0;

  const myUserId = myPosition?.user_id;

  return (
    <div className="overflow-x-auto rounded-xl border border-slate-800 bg-slate-900">
      <table className="min-w-full text-left text-sm">
        <thead className="border-b border-slate-800 text-slate-300">
        <tr>
          <th className="px-4 py-3">#</th>
          <th className="px-4 py-3">Usuario</th>
          <th className="px-4 py-3">Kudos</th>
        </tr>
        </thead>
        <tbody>
        {showLoadingRow && (
          <tr>
            <td colSpan="3" className="px-4 py-6 text-sm text-slate-400">Cargando ranking de usuarios...</td>
          </tr>
        )}

        {showErrorRow && (
          <tr>
            <td colSpan="3" className="px-4 py-6 text-sm text-red-200">Hubo un problema al cargar el ranking.</td>
          </tr>
        )}

        {showEmptyRow && (
          <tr>
            <td colSpan="3" className="px-4 py-6 text-sm text-slate-400">Aún no hay datos de ranking para mostrar.</td>
          </tr>
        )}

        {showDataRows && rows.map((row) => {
          const isCurrentUser = myUserId && row.id === myUserId;
          return (
            <tr
              className={`border-b border-slate-800/70 last:border-0 ${isCurrentUser ? 'bg-indigo-950/30' : ''}`}
              key={row.id}
            >
              <td className="px-4 py-3 font-medium text-slate-200">{row.rank}</td>
              <td className="px-4 py-3 text-slate-300">{row.name}</td>
              <td className="px-4 py-3 text-slate-200">{row.total_kudos}</td>
            </tr>
          );
        })}
        </tbody>
      </table>
    </div>
  );
};