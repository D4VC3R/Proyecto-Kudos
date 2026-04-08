import React from 'react';
import { useCategories } from './../hooks/categories/useCategoryQueries';
import { useLogin } from './../hooks/auth/useAuthMutations';
import { useSessionStore, selectIsAuthenticated } from './../store/useSessionStore';

const HomePage = () => {
  // Estado global
  const isAuthenticated = useSessionStore(selectIsAuthenticated);

  // Consultas al servidor
  const { data: categories, isLoading, isError, error } = useCategories();

  // Mutaciones
  const { mutate: login, isPending: isLoggingIn } = useLogin();

  if (isLoading) return <div>Cargando el listado...</div>;
  if (isError) return <div>Algo salió mal: {error.message}</div>;

  return (
    <main>
      <h1>Categorías</h1>
      <ul>
        {categories?.map(category => (
          <li key={category.id}>{category.name}</li>
        ))}
      </ul>
    </main>
  );
};

export default HomePage;