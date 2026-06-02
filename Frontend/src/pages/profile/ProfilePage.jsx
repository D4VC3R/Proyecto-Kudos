import React, {useState} from 'react';
import {User as UserIcon} from 'lucide-react';
import {profileTabs} from "../../lib/constants.js";
// Componentes
import ProfileOverview from './ProfileOverview.jsx';
import MyProposalsPage from './MyProposalsPage.jsx';
import MyVotesPage from './MyVotesPage.jsx';
import MyStatsPage from './MyStatsPage.jsx';
import ProfileInfoFormSkeleton from '../../components/profile/ProfileInfoFormSkeleton.jsx';
import SectionHeader from "../../components/ui/SectionHeader.jsx";
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
        <SectionHeader
          size="large"
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
      <div className="w-full min-h-[50vh]">
        {!isLoading && (isError || !profile) ? (
          <FeedbackState
            icon={UserIcon}
            iconColorClass="bg-slate-100 text-slate-400"
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
          </>
        )}
      </div>
    </div>
  );
};

export default ProfilePage;