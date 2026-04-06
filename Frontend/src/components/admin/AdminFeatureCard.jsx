import { Link } from 'react-router-dom';

export const AdminFeatureCard = ({ to, title, subtitle, accentClass, icon }) => {
  return (
    <Link
      className="group overflow-hidden rounded-2xl border border-slate-800 bg-slate-900 transition hover:border-slate-600 hover:shadow-lg"
      to={to}
    >
      <article className="flex h-full flex-col">
        <div className={`flex flex-1 items-center justify-center aspect-[16/9] xl:aspect-[9/16] ${accentClass}`}>
          <span aria-hidden className="text-6xl text-white/90 drop-shadow-sm">
            {icon}
          </span>
        </div>
        <footer className="space-y-1 border-t border-slate-800 bg-slate-950/70 p-4">
          <h2 className="text-base font-semibold text-slate-100 group-hover:text-white">{title}</h2>
          <p className="min-h-[2.5rem] text-sm text-slate-400 line-clamp-2">{subtitle}</p>
        </footer>
      </article>
    </Link>
  );
};
