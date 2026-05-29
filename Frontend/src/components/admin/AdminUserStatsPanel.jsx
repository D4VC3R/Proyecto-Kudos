import React from 'react';
import SectionHeader from '../ui/SectionHeader.jsx';
import StatCard from './StatCard';
import {Award, FileText, CheckCircle, MessageSquare, PlusSquare, Eye, Key} from 'lucide-react';

const AdminUserStatsPanel = ({user}) => {
  return (
    <>
      <SectionHeader title="Estadísticas" highlight="Kudos" icon={Award}/>

      <div className="grid grid-cols-2 lg:grid-cols-4 gap-4">
        <StatCard
          icon={Award}
          value={user.total_kudos}
          label="Kudos"
          iconColor="text-amber-500"
          containerClass="bg-gradient-to-br from-yellow-100 to-amber-100 border-yellow-200"
        />
        <StatCard icon={CheckCircle} value={user.creations_accepted} label="Aceptados" iconColor="text-blue-500"/>
        <StatCard icon={FileText} value={user.proposals_count} label="Propuestas" iconColor="text-purple-500"/>
        <StatCard icon={CheckCircle} value={user.votes_count} label="Votos Emitidos" iconColor="text-green-500"/>
      </div>

      <div className="grid grid-cols-2 lg:grid-cols-4 gap-4 mt-2">
        <StatCard icon={MessageSquare} value={user.comments_count} label="Comentarios" iconColor="text-indigo-500"
                  valueClass="text-2xl"/>
        <StatCard icon={PlusSquare} value={user.items_count} label="Items Creados" iconColor="text-pink-500"
                  valueClass="text-2xl"/>
        {user.role === 'admin' && (
          <StatCard icon={Eye} value={user.reviewed_proposals_count} label="Revisiones" iconColor="text-teal-500"
                    valueClass="text-2xl"/>
        )}
        <StatCard icon={Key} value={user.sessions_count} label="Sesiones Activas" iconColor="text-orange-500"
                  valueClass="text-2xl"/>
      </div>
    </>
  );
};

export default AdminUserStatsPanel;

