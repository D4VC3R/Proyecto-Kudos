import { AdminDashboardGrid } from '../components/admin/AdminDashboardGrid';
import { AdminSectionShell } from '../components/admin/AdminSectionShell';
import { StateCard } from '../components/common/StateCard';
import { AsyncSection } from '../components/common/AsyncSection';
import { useAdminUsersSummaryQuery } from '../hooks/admin';

export const AdminPage = () => {
  const { data, isLoading, isError, error } = useAdminUsersSummaryQuery();

  const totalUsers = data?.meta?.summary?.total_users ?? 0;

  const cards = [
    {
      key: 'users',
      to: '/admin/users',
      title: 'Usuarios',
      subtitle: `Usuarios totales: ${totalUsers}`,
      icon: 'U',
      accentClass: 'bg-gradient-to-br from-indigo-700 to-indigo-900',
    },
    {
      key: 'items',
      to: '/admin/items',
      title: 'Items',
      subtitle: 'Gestionar items y moderacion asociada',
      icon: 'I',
      accentClass: 'bg-gradient-to-br from-emerald-700 to-emerald-900',
    },
    {
      key: 'categories',
      to: '/admin/categories',
      title: 'Categorias',
      subtitle: 'Gestion de categorias y configuracion',
      icon: 'C',
      accentClass: 'bg-gradient-to-br from-amber-700 to-amber-900',
    },
    {
      key: 'proposals',
      to: '/admin/proposals',
      title: 'Proposals',
      subtitle: 'Revision de propuestas pendientes',
      icon: 'P',
      accentClass: 'bg-gradient-to-br from-fuchsia-700 to-fuchsia-900',
    },
  ];

  return (
      <AdminSectionShell
          subtitle="Selecciona una seccion para acceder a su subvista de administracion."
          title="Dashboard admin"
      >
        <AsyncSection
            ErrorComponent={({ error }) => <StateCard error={error} tone="error" />}
            LoadingComponent={() => <StateCard message="Cargando dashboard admin..." />}
            error={error}
            isError={isError}
            isLoading={isLoading}
        >
          <AdminDashboardGrid cards={cards} />
        </AsyncSection>
      </AdminSectionShell>
  );
};