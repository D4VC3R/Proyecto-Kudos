import React from 'react';
import { Link } from 'react-router-dom';
import { UserX, UserRound } from 'lucide-react';
// Componentes
import SectionHead from '../components/ui/SectionHead.jsx';
import Button from '../components/ui/Button.jsx';
import FeedbackState from '../components/ui/FeedbackState.jsx';
import HeaderSkeleton from '../components/ui/skeletons/HeaderSkeleton.jsx';
import SliderSkeleton from '../components/ui/skeletons/SliderSkeleton.jsx';
import ReportMenu from '../components/reports/ReportMenu.jsx';
import ProfileIdentityCard from '../components/profile/ProfileIdentityCard.jsx';
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
      <ProfileIdentityCard
        headingAs="h1"
        name={profile.name}
        username={profile.username}
        avatar={profile.avatar}
        biography={profile.biography}
      >
        {state.canReport && <ReportMenu type="user" id={profile.id} label="perfil" />}
      </ProfileIdentityCard>

      {state.isOwner && (
        <p role="status" className="text-small text-ink-muted">
          {profile.is_public ? (
            'Así ven tu perfil los demás.'
          ) : (
            <>
              Tu perfil es privado: solo lo ves tú. Puedes hacerlo público en{' '}
              <Button as={Link} to="/profile" variant="link">Mi perfil</Button>.
            </>
          )}
        </p>
      )}

      {state.topByCategory.length > 0 && (
        <section aria-labelledby="profile-favorites" className="flex flex-col gap-4">
          <SectionHead id="profile-favorites" title="Sus favoritos" />
          {state.topByCategory.map((group) => (
            <PublicTopCategoryRow key={group.category.id} group={group} />
          ))}
        </section>
      )}

      {state.lists.length > 0 && (
        <section aria-labelledby="profile-lists" className="flex flex-col gap-4">
          <SectionHead id="profile-lists" title="Listas" />
          <ul className="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
            {state.lists.map((list) => (
              <PublicListCard key={list.id} list={list} />
            ))}
          </ul>
        </section>
      )}

      {state.reviews.length > 0 && (
        <section aria-labelledby="profile-reviews" className="flex flex-col gap-4">
          <SectionHead id="profile-reviews" title="Reseñas recientes" />
          <ul className="flex flex-col gap-3">
            {state.reviews.map((review) => (
              <PublicReviewCard key={review.id} review={review} />
            ))}
          </ul>
        </section>
      )}

      {state.isEmpty && (
        <FeedbackState icon={UserRound} titleAs="h2" title="Todavía no hay actividad pública" />
      )}
    </div>
  );
};

export default PublicProfilePage;
