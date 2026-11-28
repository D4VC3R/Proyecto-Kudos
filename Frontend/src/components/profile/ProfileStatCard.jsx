import React from 'react';

// Una cifra del resumen de estadísticas. Va dentro de un `ul`.
const ProfileStatCard = ({ title, value, icon: Icon }) => {
  return (
    <li className="flex items-center gap-4 rounded-md border border-line bg-surface p-4">
      <span aria-hidden="true" className="flex h-12 w-12 shrink-0 items-center justify-center rounded-sm bg-accent/10 text-accent">
        <Icon size={24} />
      </span>
      <div className="flex min-w-0 flex-col">
        <p className="text-caption font-semibold uppercase tracking-widest text-ink-muted">{title}</p>
        <p className="font-display text-h2 tabular-nums text-ink">{value}</p>
      </div>
    </li>
  );
};

export default ProfileStatCard;
