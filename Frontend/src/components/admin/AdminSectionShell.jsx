import { AdminSectionHeader } from './shared/AdminSectionHeader';

export const AdminSectionShell = ({ title, subtitle, children }) => {
  return (
    <section className="space-y-4">
      <AdminSectionHeader
        subtitle={subtitle}
        subtitleClassName="text-sm text-slate-400"
        title={title}
        titleAs="h1"
        titleClassName="text-2xl font-bold text-slate-100"
      />
      {children}
    </section>
  );
};
