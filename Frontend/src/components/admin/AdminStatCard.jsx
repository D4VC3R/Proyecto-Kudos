import React from 'react';
import Card from '../ui/Card.jsx';

const AdminStatCard = ({ icon: Icon, value, label, children }) => {
  return (
    <Card className="flex flex-col items-center justify-center gap-1 text-center">
      <Icon className="text-ink-muted" size={24} aria-hidden="true" />
      {children ?? <span className="font-display text-h2 text-ink">{value || 0}</span>}
      <span className="text-caption font-semibold uppercase tracking-wide text-ink-muted">{label}</span>
    </Card>
  );
};

export default AdminStatCard;
