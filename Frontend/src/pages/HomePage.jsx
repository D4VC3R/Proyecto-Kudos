import { Link } from 'react-router-dom';

export const HomePage = () => {
  return (
    <section className="space-y-6">
      <header className="rounded-2xl border border-slate-800 bg-slate-900/70 p-6">
        <h1 className="text-3xl font-bold">Kudos Frontend</h1>
        <p className="mt-2 text-slate-300">
          Base minima navegable lista para iterar por modulos: votacion, ranking, perfil y panel admin.
        </p>
      </header>

      <div className="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
        <article className="rounded-xl border border-slate-800 bg-slate-900 p-4">
          <h2 className="font-semibold">Categorias y votacion</h2>
          <p className="mt-2 text-sm text-slate-400">Conecta luego con next-item y flujo idempotente de votos.</p>
        </article>
        <article className="rounded-xl border border-slate-800 bg-slate-900 p-4">
          <h2 className="font-semibold">Ranking</h2>
          <p className="mt-2 text-sm text-slate-400">Pantalla publica preparada para integrar posicion personal.</p>
        </article>
        <article className="rounded-xl border border-slate-800 bg-slate-900 p-4">
          <h2 className="font-semibold">Admin</h2>
          <p className="mt-2 text-sm text-slate-400">Ruta protegida por sesion, verificacion y rol administrador.</p>
        </article>
      </div>

      <div className="flex gap-3">
        <Link className="rounded-md bg-indigo-600 px-4 py-2 text-sm font-medium hover:bg-indigo-500" to="/ranking">
          Ver ranking
        </Link>
        <Link className="rounded-md border border-slate-700 px-4 py-2 text-sm font-medium hover:bg-slate-800" to="/categories/demo/vote">
          Ir a votacion demo
        </Link>
      </div>
    </section>
  );
};
