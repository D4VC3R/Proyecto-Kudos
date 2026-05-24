import React, {useState} from 'react';
import {User as UserIcon} from 'lucide-react';
import {useProfile} from '../../hooks/users/useUserQueries';
import {ProfileOverview} from '../../components/profile/ProfileOverview.jsx';
import {MyProposalsPage} from '../proposals/MyProposalsPage';
import {MyVotesPage} from '../votes/MyVotesPage';
import {ProfileStatistics} from '../../components/profile/ProfileStatistics';
import {ProfileInfoFormSkeleton} from '../../components/profile/ProfileInfoFormSkeleton';
import {SectionHeader} from "../../components/common/SectionHeader.jsx";
import {ProfileTabs} from "../../components/profile/ProfileTabs.jsx";
import {profileTabs} from "../../lib/constants.js";
import {FeedbackState} from '../../components/common/FeedbackState';

export const ProfilePage = () => {
  const [activeTab, setActiveTab] = useState('info');
  const {data: profile, isLoading, isError} = useProfile();


  if (!isLoading && (isError || !profile)) {
    return (
      <div className="flex h-[50vh] w-full flex-col items-center justify-center">
        <FeedbackState
          icon={UserIcon}
          iconColorClass="bg-slate-100 text-slate-400"
          title="Error al cargar perfil"
          description="No se pudo obtener la información de tu perfil. Intenta iniciar sesión nuevamente."
        />
      </div>
    );
  }

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
          <ProfileStatistics/>
        )}
      </div>
    </div>
  );
};

export default ProfilePage;