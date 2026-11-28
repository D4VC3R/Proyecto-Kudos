import React from 'react';
import SectionHead from '../ui/SectionHead.jsx';
import KudosBadge from '../ui/KudosBadge.jsx';
import AdminStatCard from './AdminStatCard.jsx';
import { Award, FileText, CheckCircle, MessageSquare, PlusSquare, Eye, Key } from 'lucide-react';

const AdminUserStatsPanel = ({ user }) => {
  return (
    <>
      <SectionHead as="h3" title="Estadísticas"/>

      <div className="grid grid-cols-2 gap-4 lg:grid-cols-4">
        <AdminStatCard icon={Award} label="Kudos">
          <KudosBadge value={user.total_kudos ?? 0} size="lg"/>
        </AdminStatCard>
        <AdminStatCard icon={CheckCircle} value={user.creations_accepted} label="Aceptados"/>
        <AdminStatCard icon={FileText} value={user.proposals_count} label="Propuestas"/>
        <AdminStatCard icon={CheckCircle} value={user.votes_count} label="Votos emitidos"/>
        <AdminStatCard icon={MessageSquare} value={user.comments_count} label="Comentarios"/>
        <AdminStatCard icon={PlusSquare} value={user.items_count} label="Ítems creados"/>
        {user.role === 'admin' && (
          <AdminStatCard icon={Eye} value={user.reviewed_proposals_count} label="Revisiones"/>
        )}
        <AdminStatCard icon={Key} value={user.sessions_count} label="Sesiones activas"/>
      </div>
    </>
  );
};

export default AdminUserStatsPanel;
