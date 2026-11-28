import React from 'react';
import {Loader2, AlertCircle, BarChart3, Star, Award, MessageSquare, ThumbsUp, EyeOff, BookmarkPlus, Flame} from 'lucide-react';
// Componentes
import ProfileStatCard from '../../components/profile/ProfileStatCard.jsx';
import SectionHead from "../../components/ui/SectionHead.jsx";
// Hooks
import {useProfileStatistics} from '../../hooks/users/useUserQueries.js';
import FeedbackState from "../../components/ui/FeedbackState.jsx";

const MyStatsPage = () => {
  const {data: stats, isLoading, isError} = useProfileStatistics();

  return (
    <div className="flex w-full flex-col relative">
      <div className="relative flex flex-col gap-6">
        <SectionHead title="Tus Estadísticas" />
        <p className="text-small text-ink-muted">Así estás usando Proyecto Kudos.</p>
        {isLoading ? (
          <FeedbackState icon={Loader2} isLoading/>
        ) : (isError || !stats) ? (
          <FeedbackState
            icon={AlertCircle}
            titleAs="h3"
            title="No hemos podido cargar tus estadísticas"
            description="Hubo un problema al obtener tu información. Inténtalo más tarde."
          />
        ) : (
          <ul className="grid grid-cols-1 gap-4 md:grid-cols-2 lg:grid-cols-3">
            <ProfileStatCard
              title="Kudos Globales"
              value={stats.total_kudos}
              icon={Award}
            />
            <ProfileStatCard
              title="Racha Actual"
              value={stats.current_login_streak > 1 ? `${stats.current_login_streak} días` : `${stats.current_login_streak} día`}
              icon={Flame}
            />
            <ProfileStatCard
              title="Mayor Racha"
              value={stats.max_login_streak > 1 ? `${stats.max_login_streak} días` : `${stats.max_login_streak} día`}
              icon={Flame}
            />
            <ProfileStatCard
              title="Votos Totales"
              value={stats.total_votes}
              icon={ThumbsUp}
            />
            <ProfileStatCard
              title="No me interesan"
              value={stats.total_not_interested ?? stats.total_skips}
              icon={EyeOff}
            />
            <ProfileStatCard
              title="En tu wishlist"
              value={stats.total_want ?? 0}
              icon={BookmarkPlus}
            />
            <ProfileStatCard
              title="Puntuación Media"
              value={stats.average_score !== null ? stats.average_score : '-'}
              icon={Star}
            />
            <ProfileStatCard
              title="Propuestas Aceptadas"
              value={stats.accepted_proposals}
              icon={BarChart3}
            />
            <ProfileStatCard
              title="Comentarios"
              value={stats.total_comments}
              icon={MessageSquare}
            />
            <ProfileStatCard
              title="Categoría Favorita"
              value={stats.favorite_category || '-'}
              icon={Star}
            />
          </ul>
        )}
      </div>
    </div>
  );
};

export default MyStatsPage;
