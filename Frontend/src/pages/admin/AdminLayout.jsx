import { Outlet, Navigate, useLocation } from 'react-router-dom';
import PageHeader from '../../components/ui/PageHeader.jsx';
import AdminNav from '../../components/admin/AdminNav.jsx';

const AdminLayout = () => {
  const location = useLocation();

  if (location.pathname === '/admin' || location.pathname === '/admin/') {
    return <Navigate to="/admin/users" replace/>;
  }

  return (
    <div className="flex w-full flex-col gap-6 animate-fade-in">
      <PageHeader title="Administración" subtitle="Centro de gestión y moderación de la aplicación"/>
      <AdminNav/>
      <Outlet/>
    </div>
  );
};

export default AdminLayout;
