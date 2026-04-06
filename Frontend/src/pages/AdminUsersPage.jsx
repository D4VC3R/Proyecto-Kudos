import { AdminSectionShell } from '../components/admin/AdminSectionShell';
import { AdminUsersSection } from '../components/admin/AdminUsersSection';
import { AdminUsersProvider } from '../context/adminUsersContext';

const AdminUsersPageContent = () => {
  return (
    <AdminSectionShell subtitle="Gestion de cuentas y estado de acceso de usuarios registrados." title="Administracion de usuarios">
      <AdminUsersSection />
    </AdminSectionShell>
  );
};

export const AdminUsersPage = () => {
  return (
    <AdminUsersProvider>
      <AdminUsersPageContent />
    </AdminUsersProvider>
  );
};
