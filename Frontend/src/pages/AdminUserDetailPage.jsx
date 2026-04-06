import { Link } from 'react-router-dom';
import { AdminSectionShell } from '../components/admin/AdminSectionShell';
import { AdminUserDetailActions } from '../components/admin/AdminUserDetailActions';
import { AdminUserDetailCard } from '../components/admin/AdminUserDetailCard';
import { StateCard } from '../components/common/StateCard';
import { AsyncSection } from '../components/common/AsyncSection';
import { AdminUserDetailProvider } from '../context/adminUserDetailContext';
import { useAdminUserDetailContext } from '../hooks/admin';

const AdminUserDetailPageContent = () => {
  const { isLoadingUserDetail, isUserDetailError, userDetailError, user } = useAdminUserDetailContext();

  return (
    <AdminSectionShell
      subtitle="Ficha ampliada de usuario para moderacion, estado y actividad."
      title="Detalle de usuario"
    >
      <Link className="inline-block rounded-md border border-slate-700 px-3 py-2 text-sm text-slate-100 transition-colors hover:bg-slate-800" to="/admin/users">
        Volver a usuarios
      </Link>

      <AsyncSection
        EmptyComponent={() => <StateCard message="No se encontro el usuario solicitado." />}
        ErrorComponent={({ error }) => <StateCard error={error} tone="error" />}
        LoadingComponent={() => <StateCard message="Cargando detalle de usuario..." />}
        error={userDetailError}
        isEmpty={!user}
        isError={isUserDetailError}
        isLoading={isLoadingUserDetail}
      >
        <>
          <AdminUserDetailCard />
          <AdminUserDetailActions />
        </>
      </AsyncSection>
    </AdminSectionShell>
  );
};

export const AdminUserDetailPage = () => {
  return (
    <AdminUserDetailProvider>
      <AdminUserDetailPageContent />
    </AdminUserDetailProvider>
  );
};