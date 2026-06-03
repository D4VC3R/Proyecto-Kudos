import React from 'react';
import {Loader2, AlertCircle, BarChart3, Star, Award, MessageSquare, ThumbsUp, SkipForward, Flame} from 'lucide-react';
// Componentes
import StatCard from '../../components/profile/StatCard.jsx';
import SectionHeader from "../../components/ui/SectionHeader.jsx";
import FadeUp from "../../components/animations/FadeUp.jsx"; // Añadido para estandarizar diseño
// Hooks
import {useProfileStatistics} from '../../hooks/users/useUserQueries.js';
import FeedbackState from "../../components/ui/FeedbackState.jsx";

const MyStatsPage = () => {
  const {data: stats, isLoading, isError} = useProfileStatistics();

  return (
    <div className="flex w-full flex-col relative">
      <FadeUp className="flex flex-col gap-6 relative">
        <SectionHeader
          title="Tus"
          highlight="Estadísticas"
          subtitle="Así estás usando Proyecto Kudos."
        />
        <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
          {isLoading ? (
            <div className="col-span-full flex flex-col ">
              <FeedbackState icon={Loader2} isLoading/>
            </div>
          ) : (isError || !stats) ? (
            <div className="col-span-full flex flex-col">
              <FeedbackState
                icon={AlertCircle}
                iconColorClass="text-red-400"
                title="No hemos podido cargar tus estadísticas"
                description="Hubo un problema al obtener tu información. Inténtalo más tarde."
              />
            </div>
          ) : (
            <>
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
                colorClass="bg-slate-100 text-text-normal"
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
            </>
          )}
        </div>
      </FadeUp>
    </div>
  );
};

export default MyStatsPage;