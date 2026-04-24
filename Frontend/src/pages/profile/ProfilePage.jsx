import React, { useState } from 'react';
import { User as UserIcon, List, ThumbsUp, BarChart3 } from 'lucide-react';
import clsx from 'clsx';
import { useProfile } from '../../hooks/users/useUserQueries';
import { ProfileInfoForm } from '../../components/profile/ProfileInfoForm';
import { MyProposalsPage } from '../proposals/MyProposalsPage';
import { MyVotesPage } from '../votes/MyVotesPage';
import { ProfileStatistics } from '../../components/profile/ProfileStatistics';
import { ProfileInfoFormSkeleton } from '../../components/profile/ProfileInfoFormSkeleton'; // <-- Importamos skeleton

export const ProfilePage = () => {
  const [activeTab, setActiveTab] = useState('info');
  const [votesFilters, setVotesFilters] = useState({ type: 'all', category_slug: undefined });

  const { data: profile, isLoading, isError } = useProfile();

  const tabs = [
    { id: 'info', name: 'Información', icon: UserIcon },
    { id: 'proposals', name: 'Mis Propuestas', icon: List },
    { id: 'votes', name: 'Historial de Votos', icon: ThumbsUp },
    { id: 'stats', name: 'Estadísticas', icon: BarChart3 },
  ];

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
    <div className="mx-auto flex w-full max-w-7xl flex-col gap-6 px-4 py-8 sm:px-6 lg:px-8">

      <div className="mb-6 flex flex-col gap-2">
        <h1 className="text-3xl font-black tracking-tight text-slate-900 sm:text-4xl">
          Mi <span className="text-blue-600 drop-shadow-sm">Perfil</span>
        </h1>
        <p className="text-lg font-medium text-slate-500">
          Gestiona tu identidad, revisa tus contribuciones y analiza tus estadísticas.
        </p>
      </div>

      <div className="w-full flex overflow-x-auto border-b border-slate-200 scrollbar-hide mb-6 gap-2 md:gap-8 min-w-0">
        {tabs.map((tab) => (
          <button
            key={tab.id}
            onClick={() => setActiveTab(tab.id)}
            className={clsx(
              "flex whitespace-nowrap items-center gap-2 py-4 px-1 md:px-2 border-b-2 font-bold transition-all text-sm md:text-base",
              activeTab === tab.id
                ? "border-blue-600 text-blue-600"
                : "border-transparent text-slate-500 hover:text-slate-800 hover:border-slate-300"
            )}
          >
            <tab.icon size={18} />
            <span>{tab.name}</span>
          </button>
        ))}
      </div>

      <div className="w-full min-h-[50vh]">
        {activeTab === 'info' && (
          isLoading ? <ProfileInfoFormSkeleton /> : <ProfileInfoForm profile={profile} />
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