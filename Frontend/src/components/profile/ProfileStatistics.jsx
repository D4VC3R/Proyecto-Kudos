import React from 'react';
import { useProfileStatistics } from '../../hooks/users/useUserQueries';
import { Loader2, AlertCircle, BarChart3, Star, Award, MessageSquare, ThumbsUp, SkipForward, Flame } from 'lucide-react';
import { StatCard } from '../common/StatCard';

export const ProfileStatistics = () => {
  const { data: stats, isLoading, isError } = useProfileStatistics();
  if (isLoading) {
    return (
      <div className="flex h-64 items-center justify-center">
        <Loader2 className="animate-spin text-blue-500" size={48} />
      </div>
    );
  }
  if (isError || !stats) {
    return (
      <div className="flex h-64 flex-col items-center justify-center text-center bg-white rounded-3xl border border-red-200 p-8">
        <AlertCircle size={48} className="text-red-400 mb-4" />
        <h3 className="text-xl font-bold text-slate-800 mb-2">No se pudieron cargar las estadísticas</h3>
        <p className="text-slate-500 max-w-sm">Hubo un problema al obtener tu información. Inténtalo más tarde.</p>
      </div>
    );
  }
  return (
    <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
      <StatCard 
        title="Kudos Globales" 
        value={stats.total_kudos} 
        icon={Award} 
        colorClass="bg-yellow-100 text-yellow-600" 
        delay={0.1}
      />
      <StatCard 
        title="Racha Actual"
        value={stats.current_login_streak > 1 ? `${stats.current_login_streak} días` : `${stats.current_login_streak} día`}
        icon={Flame} 
        colorClass="bg-orange-100 text-orange-500" 
        delay={0.2}
      />
      <StatCard 
        title="Mayor Racha"
        value={stats.max_login_streak > 1 ? `${stats.max_login_streak} días` : `${stats.max_login_streak} día`}
        icon={Flame} 
        colorClass="bg-red-100 text-red-500" 
        delay={0.3}
      />
      <StatCard 
        title="Votos Totales" 
        value={stats.total_votes} 
        icon={ThumbsUp} 
        colorClass="bg-blue-100 text-blue-500" 
        delay={0.4}
      />
      <StatCard 
        title="Skips Realizados" 
        value={stats.total_skips} 
        icon={SkipForward} 
        colorClass="bg-slate-100 text-slate-500" 
        delay={0.5}
      />
      <StatCard 
        title="Puntuación Media" 
        value={stats.average_score !== null ? stats.average_score : '-'} 
        icon={Star} 
        colorClass="bg-amber-100 text-amber-500" 
        delay={0.6}
      />
      <StatCard 
        title="Propuestas Aceptadas" 
        value={stats.accepted_proposals} 
        icon={BarChart3} 
        colorClass="bg-emerald-100 text-emerald-500" 
        delay={0.7}
      />
      <StatCard 
        title="Comentarios" 
        value={stats.total_comments} 
        icon={MessageSquare} 
        colorClass="bg-purple-100 text-purple-500" 
        delay={0.8}
      />
      <StatCard 
        title="Categoría Favorita" 
        value={stats.favorite_category || '-'} 
        icon={Star} 
        colorClass="bg-pink-100 text-pink-500" 
        delay={0.9}
      />
    </div>
  );
};
