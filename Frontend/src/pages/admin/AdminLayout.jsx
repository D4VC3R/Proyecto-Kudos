import { NavLink, Outlet, Navigate, useLocation } from 'react-router-dom';
import { SectionHeader } from '../../components/common/SectionHeader.jsx';
import { Users, Target, LayoutGrid, FileText } from 'lucide-react';
import { clsx } from 'clsx';
import { twMerge } from 'tailwind-merge';
const adminTabs = [
  { name: 'Usuarios', to: '/admin/users', icon: Users },
  { name: 'Propuestas', to: '/admin/proposals', icon: FileText },
  { name: 'Categorías', to: '/admin/categories', icon: LayoutGrid },
  { name: 'Items', to: '/admin/items', icon: Target },
];
export const AdminLayout = () => {
  const location = useLocation();
  if (location.pathname === '/admin' || location.pathname === '/admin/') {
    return <Navigate to="/admin/users" replace />;
  }
  return (
    <div className="flex flex-col gap-6 w-full max-w-7xl mx-auto px-4 sm:px-6 animate-fade-in">
      <SectionHeader 
        title="Admin" 
        highlight="Dashboard" 
        highlightColor="yellow-500"
        subtitle="Centro de gestión y moderación de la aplicación"
      />
      <nav className="flex space-x-2 bg-white/60 backdrop-blur-md p-2 rounded-3xl overflow-x-auto shadow-sm ring-1 ring-slate-900/5">
        {adminTabs.map((tab) => {
          const Icon = tab.icon;
          return (
            <NavLink
              key={tab.name}
              to={tab.to}
              className={({ isActive }) => twMerge(
                clsx(
                  "flex items-center gap-2 px-5 py-3 rounded-2xl font-bold transition-all whitespace-nowrap",
                  isActive 
                    ? "bg-blue-600 text-white shadow-md shadow-blue-500/30 ring-2 ring-blue-600 ring-offset-2 ring-offset-slate-50" 
                    : "text-slate-600 hover:bg-slate-100 hover:text-slate-900 hover:shadow-sm"
                )
              )}
            >
              <Icon size={18} strokeWidth={2.5} />
              {tab.name}
            </NavLink>
          );
        })}
      </nav>
      <div className="bg-white rounded-3xl shadow-sm ring-1 ring-slate-900/5 p-4 sm:p-6 md:p-8 min-h-[50vh]">
        <Outlet />
      </div>
    </div>
  );
};
