import { AsyncSection } from '../components/common/AsyncSection';
import { ProfileForm } from '../components/profile/ProfileForm';
import { ProfileHeader } from '../components/profile/ProfileHeader';
import { ProfileErrorStatus, ProfileLoadingStatus } from '../components/profile/ProfileStatus';
import { ProfileStatsCard } from '../components/profile/ProfileStatsCard';
import { ProfileProvider } from '../context/profileContext';
import { useProfileContext } from '../hooks/useProfileContext';

const ProfilePageContent = () => {
  const { isLoadingProfile, isProfileError, profileError } = useProfileContext();

  return (
    <section className="space-y-4">
      <ProfileHeader />

      <AsyncSection
        ErrorComponent={ProfileErrorStatus}
        LoadingComponent={ProfileLoadingStatus}
        error={profileError}
        isError={isProfileError}
        isLoading={isLoadingProfile}
      >
        <>
          <ProfileStatsCard />
          <ProfileForm />
        </>
      </AsyncSection>
    </section>
  );
};

export const ProfilePage = () => {
  return (
    <ProfileProvider>
      <ProfilePageContent />
    </ProfileProvider>
  );
};
