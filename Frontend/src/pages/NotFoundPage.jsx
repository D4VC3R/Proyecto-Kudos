import { Link } from 'react-router-dom';

export const NotFoundPage = () => {
  return (
    <section className="mx-auto w-full max-w-xl rounded-2xl border border-slate-800 bg-slate-900 p-8 text-center">
      <h1 className="text-3xl font-bold">404</h1>
      <p className="mt-2 text-slate-300">La ruta solicitada no existe.</p>
      <Link className="mt-5 inline-block rounded-md bg-indigo-600 px-4 py-2 text-sm font-medium hover:bg-indigo-500" to="/">
        Volver al inicio
      </Link>
    </section>
  );
};
