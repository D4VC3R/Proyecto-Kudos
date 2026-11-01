import React from 'react';
import { Link } from 'react-router-dom';
import { UserX, UserRound } from 'lucide-react';
// Componentes
import SectionHeader from '../components/ui/SectionHeader.jsx';
import FeedbackState from '../components/ui/FeedbackState.jsx';
import HeaderSkeleton from '../components/ui/skeletons/HeaderSkeleton.jsx';
import SliderSkeleton from '../components/ui/skeletons/SliderSkeleton.jsx';
import ReportMenu from '../components/reports/ReportMenu.jsx';
import PublicProfileHeader from '../components/profile/PublicProfileHeader.jsx';
import PublicTopCategoryRow from '../components/profile/PublicTopCategoryRow.jsx';
import PublicListCard from '../components/profile/PublicListCard.jsx';
import PublicReviewCard from '../components/profile/PublicReviewCard.jsx';
// Hooks
import { usePublicProfilePage } from '../hooks/pages/usePublicProfilePage.js';

const PublicProfilePage = () => {
  const { state, actions } = usePublicProfilePage();

  if (state.isLoading) {
    return (
      <div className="flex w-full flex-col gap-8">
        <HeaderSkeleton />
        <SliderSkeleton />
      </div>
    );
  }

  if (state.isError || !state.profile) {
    return (
      <FeedbackState
        icon={UserX}
        title="No encontramos este perfil"
        description="Puede que sea privado o que no exista."
        actionText="Volver al inicio"
        onAction={actions.handleGoHome}
      />
    );
  }

  const { profile } = state;

  return (
    <div className="flex w-full flex-col gap-8">
      <PublicProfileHeader profile={profile}>
        {state.canReport && <ReportMenu type="user" id={profile.id} label="perfil" />}
      </PublicProfileHeader>

      {state.isOwner && (
        <p role="status" className="text-sm font-bold text-text-normal">
          {profile.is_public ? (
            'Así ven tu perfil los demás.'
          ) : (
            <>
              Tu perfil es privado: solo lo ves tú. Puedes hacerlo público en{' '}
              <Link to="/profile" className="text-primary hover:underline">Mi perfil</Link>.
            </>
          )}
        </p>
      )}

      {state.topByCategory.length > 0 && (
        <section className="flex flex-col gap-4">
          <SectionHeader title="Sus favoritos" />
          {state.topByCategory.map((group) => (
            <PublicTopCategoryRow key={group.category.id} group={group} onItemClick={actions.handleItemClick} />
          ))}
        </section>
      )}

      {state.lists.length > 0 && (
        <section className="flex flex-col gap-4">
          <SectionHeader title="Listas" />
          <div className="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
            {state.lists.map((list) => (
              <PublicListCard key={list.id} list={list} />
            ))}
          </div>
        </section>
      )}

      {state.reviews.length > 0 && (
        <section className="flex flex-col gap-4">
          <SectionHeader title="Reseñas recientes" />
          <div className="flex flex-col gap-3">
            {state.reviews.map((review) => (
              <PublicReviewCard key={review.id} review={review} />
            ))}
          </div>
        </section>
      )}

      {state.isEmpty && (
        <FeedbackState icon={UserRound} title="Todavía no hay actividad pública" />
      )}
    </div>
  );
};

export default PublicProfilePage;
