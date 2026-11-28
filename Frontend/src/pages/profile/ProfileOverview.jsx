import React from 'react';
import {Link} from 'react-router-dom';
import {useSessionStore} from '../../store/useSessionStore.js';
import Button from '../../components/ui/Button.jsx';
import ProfileIdentityCard from '../../components/profile/ProfileIdentityCard.jsx';
import ProfileEditForm from '../../components/profile/ProfileEditForm.jsx';

const ProfileOverview = ({profile}) => {
  const user = useSessionStore((state) => state.user);

  return (
    <div className="flex w-full flex-col gap-6 lg:flex-row lg:items-start">
      <ProfileIdentityCard
        className="lg:w-96 lg:shrink-0"
        name={user?.name}
        username={profile?.username}
        avatar={profile?.avatar}
        email={user?.email}
        kudos={profile?.total_kudos ?? user?.total_kudos ?? 0}
        isAdmin={user?.role === 'admin'}
      >
        {profile?.username && (
          <Button as={Link} to={`/u/${profile.username}`} variant="link">
            Ver mi perfil público
          </Button>
        )}
      </ProfileIdentityCard>

      <ProfileEditForm profile={profile}/>
    </div>
  );
};

export default ProfileOverview;
