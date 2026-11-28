import React, {useState} from 'react';
import {User as UserIcon} from 'lucide-react';
import {profileTabs} from "../../lib/constants.js";
// Componentes
import ProfileOverview from './ProfileOverview.jsx';
import MyProposalsPage from './MyProposalsPage.jsx';
import MyVotesPage from './MyVotesPage.jsx';
import MyStatsPage from './MyStatsPage.jsx';
import ProfilePrivacyPage from './ProfilePrivacyPage.jsx';
import ProfileInfoFormSkeleton from '../../components/ui/skeletons/ProfileInfoFormSkeleton.jsx';
import PageHeader from "../../components/ui/PageHeader.jsx";
import ProfileTabs from "../../components/profile/ProfileTabs.jsx";
import FeedbackState from '../../components/ui/FeedbackState.jsx';
// Hooks
import {useProfile} from '../../hooks/users/useUserQueries.js';

export const ProfilePage = ({tab = 'info'}) => {
  const [activeTab, setActiveTab] = useState(tab);
  const {data: profile, isLoading, isError} = useProfile();

  return (
    <div className="flex w-full flex-col">
      <div className="mb-8 shrink-0">
        <PageHeader
          title="Mi"
          highlight="Perfil"
          subtitle="Gestiona tu identidad, revisa tus contribuciones y analiza tus estadísticas."
        />
      </div>
      <ProfileTabs
        tabs={profileTabs}
        activeTab={activeTab}
        setActiveTab={setActiveTab}
      />
      <div
        role="tabpanel"
        id={`profile-panel-${activeTab}`}
        aria-labelledby={`profile-tab-${activeTab}`}
        className="w-full min-h-[50vh]"
      >
        {!isLoading && (isError || !profile) ? (
          <FeedbackState
            icon={UserIcon}
            titleAs="h2"
            title="Error al cargar perfil"
            description="No se pudo obtener la información de tu perfil. Intenta iniciar sesión de nuevo."
          />
        ) : (
          <>
            {activeTab === 'info' && (
              isLoading ? <ProfileInfoFormSkeleton/> : <ProfileOverview profile={profile}/>
            )}
            {activeTab === 'proposals' && (
              <MyProposalsPage/>
            )}
            {activeTab === 'votes' && (
              <MyVotesPage/>
            )}
            {activeTab === 'stats' && (
              <MyStatsPage/>
            )}
            {activeTab === 'privacy' && (
              <ProfilePrivacyPage/>
            )}
          </>
        )}
      </div>
    </div>
  );
};

export default ProfilePage;