import { useAdminUserDetailContext } from '../../hooks/admin/useAdminContexts';

export const AdminUserDetailCard = () => {
  const { user } = useAdminUserDetailContext();

  const metricItems = (user) => [
    { key: 'total_kudos', label: 'Kudos totales', value: user?.total_kudos ?? 0 },
    { key: 'creations_accepted', label: 'Creaciones aceptadas', value: user?.creations_accepted ?? 0 },
    { key: 'proposals_count', label: 'Propuestas', value: user?.proposals_count ?? 0 },
    { key: 'votes_count', label: 'Votos', value: user?.votes_count ?? 0 },
    { key: 'comments_count', label: 'Comentarios', value: user?.comments_count ?? 0 },
    { key: 'items_count', label: 'Items', value: user?.items_count ?? 0 },
  ];

  const metrics = metricItems(user);

  return (
    <section className="space-y-4 rounded-2xl border border-slate-800 bg-slate-900 p-5">
      <header className="space-y-1">
        <h2 className="text-xl font-semibold text-slate-100">{user?.name}</h2>
        <p className="text-sm text-slate-400">{user?.email}</p>
      </header>

      <div className="grid gap-2 text-sm text-slate-300 sm:grid-cols-2">
        <p>
          <span className="text-slate-400">Rol:</span> {user?.role ?? 'user'}
        </p>
        <p>
          <span className="text-slate-400">Estado:</span> {user?.is_banned ? 'Baneado' : 'Activo'}
        </p>
        <p>
          <span className="text-slate-400">Baneado hasta:</span> {user?.banned_until ?? 'N/A'}
        </p>
        <p>
          <span className="text-slate-400">Ciudad:</span> {user?.profile?.city ?? 'N/A'}
        </p>
      </div>

      <div className="grid gap-3 sm:grid-cols-2 lg:grid-cols-3">
        {metrics.map((metric) => (
          <article className="rounded-xl border border-slate-800 bg-slate-950 p-3" key={metric.key}>
            <p className="text-xs uppercase tracking-wide text-slate-400">{metric.label}</p>
            <p className="mt-2 text-xl font-semibold text-slate-100">{metric.value}</p>
          </article>
        ))}
      </div>
    </section>
  );
};
