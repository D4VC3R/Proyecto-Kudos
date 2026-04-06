import { AdminProposalsSection } from '../components/admin/AdminProposalsSection';
import { AdminSectionShell } from '../components/admin/AdminSectionShell';
import { AdminProposalsProvider } from '../context/adminProposalsContext';

export const AdminProposalsPage = () => {
  return (
    <AdminProposalsProvider>
      <AdminSectionShell subtitle="Subvista temporal de proposals mientras se completa su iteracion dedicada." title="Administracion de proposals">
        <AdminProposalsSection />
      </AdminSectionShell>
    </AdminProposalsProvider>
  );
};

