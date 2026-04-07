import { useProfileContext } from '../../hooks/useProfileContext';

export const ProfileStatsCard = () => {
  const { profile } = useProfileContext();

  return (
    <div className="grid gap-3 sm:grid-cols-2">
      <article className="rounded-xl border border-slate-800 bg-slate-900 p-4">
        <p className="text-xs uppercase tracking-wide text-slate-400">Kudos totales</p>
        <p className="mt-2 text-2xl font-semibold text-slate-100">{profile?.total_kudos ?? 0}</p>
      </article>
      <article className="rounded-xl border border-slate-800 bg-slate-900 p-4">
        <p className="text-xs uppercase tracking-wide text-slate-400">Creaciones aceptadas</p>
        <p className="mt-2 text-2xl font-semibold text-slate-100">{profile?.creations_accepted ?? 0}</p>
      </article>
    </div>
  );
};
