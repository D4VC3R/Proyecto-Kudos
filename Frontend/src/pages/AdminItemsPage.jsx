import { AdminItemsSection } from '../components/admin/AdminItemsSection';
import { AdminSectionShell } from '../components/admin/AdminSectionShell';
import { AdminItemsProvider } from '../context/adminItemsContext';

export const AdminItemsPage = () => {
  return (
    <AdminItemsProvider>
      <AdminSectionShell subtitle="Gestiona estado y visibilidad de items con filtros, orden server-side y moderacion auditada." title="Administracion de items">
        <AdminItemsSection />
      </AdminSectionShell>
    </AdminItemsProvider>
  );
};


