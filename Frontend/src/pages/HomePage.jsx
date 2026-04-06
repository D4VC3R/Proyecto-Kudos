import { Link } from 'react-router-dom';
import { StateCard } from '../components/common/StateCard';
import { useCategoriesQuery } from '../hooks/useCategoriesQuery';

export const HomePage = () => {
  const { data: categories, isLoading, isError, error } = useCategoriesQuery();

  const hasCategories = Array.isArray(categories) && categories.length > 0;

  return (
    <section className="space-y-6">

      <section className="space-y-3 rounded-xl border border-slate-800 bg-slate-900 p-4">
        <h2 className="text-lg font-semibold">Iniciar votacion por categoria</h2>

        {isLoading ? <StateCard message="Cargando categorias disponibles..." /> : null}
        {isError ? <StateCard error={error} tone="error" /> : null}
        {!isLoading && !isError && !hasCategories ? <StateCard message="No hay categorias disponibles por ahora." /> : null}

        {!isLoading && !isError && hasCategories ? (
          <div className="grid gap-2 sm:grid-cols-2 lg:grid-cols-3">
            {categories.map((category) => {
              return (
                <Link
                  className="rounded-md border border-slate-700 px-3 py-2 text-sm font-medium text-slate-100 hover:bg-slate-800"
                  key={category.id}
                  to={`/categories/${category.id}/vote`}
                >
                  {category.name}
                </Link>
              );
            })}
          </div>
        ) : null}
      </section>

      <div className="flex gap-3">
        <Link className="rounded-md bg-indigo-600 px-4 py-2 text-sm font-medium hover:bg-indigo-500" to="/ranking">
          Ver ranking
        </Link>
      </div>
    </section>
  );
};
