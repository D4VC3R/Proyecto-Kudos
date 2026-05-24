import { Outlet, Navigate, useLocation } from 'react-router-dom';
import { SectionHeader } from '../../components/common/SectionHeader.jsx';
import { AdminNav } from '../../components/admin/AdminNav.jsx';

const AdminLayout = () => {
  const location = useLocation();

  if (location.pathname === '/admin' || location.pathname === '/admin/') {
    return <Navigate to="/admin/users" replace />;
  }

  return (
    <div className="flex w-full flex-col animate-fade-in">
      <div className="mb-8 shrink-0">
        <SectionHeader
          size="large"
          title="Admin"
          highlight="Dashboard"
          highlightColor="yellow-500"
          subtitle="Centro de gestión y moderación de la aplicación"
        />
      </div>

      <div className="flex flex-col gap-6">
        <AdminNav />

        <div className="bg-white rounded-3xl shadow-sm ring-1 ring-slate-900/5 p-4 sm:p-6 md:p-8 min-h-[50vh]">
          <Outlet />
        </div>
      </div>
    </div>
  );
};

export default AdminLayout;