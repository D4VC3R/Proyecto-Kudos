import React, { useState } from 'react';
import { User as UserIcon } from 'lucide-react';
import { useProfile } from '../../hooks/users/useUserQueries';
import { ProfileOverview } from '../../components/profile/ProfileOverview.jsx';
import { MyProposalsPage } from '../proposals/MyProposalsPage';
import { MyVotesPage } from '../votes/MyVotesPage';
import { ProfileStatistics } from '../../components/profile/ProfileStatistics';
import { ProfileInfoFormSkeleton } from '../../components/profile/ProfileInfoFormSkeleton';
import {SectionHeader} from "../../components/common/SectionHeader.jsx";
import {ProfileTabs} from "../../components/profile/ProfileTabs.jsx";
import {profileTabs} from "../../lib/constants.js";

export const ProfilePage = () => {
  const [activeTab, setActiveTab] = useState('info');
  const [votesFilters, setVotesFilters] = useState({ type: 'all', category_slug: undefined });

  const { data: profile, isLoading, isError } = useProfile();

  if (!isLoading && (isError || !profile)) {
    return (
      <div className="flex h-[50vh] flex-col items-center justify-center text-center">
        <UserIcon size={64} className="text-slate-300 mb-4" />
        <h2 className="text-2xl font-black text-slate-800">Error al cargar perfil</h2>
        <p className="text-slate-500 max-w-md mt-2">No se pudo obtener la información de tu perfil. Intenta iniciar sesión nuevamente.</p>
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
          isLoading ? <ProfileInfoFormSkeleton /> : <ProfileOverview profile={profile} />
        )}

        {activeTab === 'proposals' && (
          <MyProposalsPage />
        )}

        {activeTab === 'votes' && (
          <MyVotesPage filters={votesFilters} setFilters={setVotesFilters} />
        )}

        {activeTab === 'stats' && (
          <ProfileStatistics />
        )}
      </div>
    </div>
  );
};

export default ProfilePage;