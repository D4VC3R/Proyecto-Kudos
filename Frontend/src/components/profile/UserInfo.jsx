import React from 'react';
import { Link } from 'react-router-dom';
import { UserCircle, Trophy, Medal } from 'lucide-react';
import { useSessionStore } from '../../store/useSessionStore.js';
import { useMinimalProfile } from '../../hooks/users/useUserQueries.js';
import { KudosCounter } from '../common/KudosCounter.jsx';

export const UserInfo = () => {
  const user = useSessionStore((state) => state.user);
  
  const { data: minimalProfile } = useMinimalProfile();

  const profile = minimalProfile;
  const position = profile?.ranking_position;
  const totalKudos = user?.total_kudos ?? 0;
  const displayName = user?.name;
  const STORAGE_URL = "http://localhost:8095/storage/";
  const displayAvatar = profile?.avatar ? `${STORAGE_URL}${profile.avatar}` : null;

  return (
    <Link 
      to="/profile" 
      className="flex items-center gap-4 bg-slate-50 hover:bg-blue-50 transition-colors border border-slate-200 rounded-full pr-4 pl-1 py-1"
    >
      <div className="w-10 h-10 rounded-full bg-blue-100 flex items-center justify-center text-blue-600 overflow-hidden shrink-0">
        {displayAvatar ? (
          <img src={displayAvatar} alt="Avatar" className="w-full h-full object-cover" />
        ) : (
          <UserCircle size={24} />
        )}
      </div>
      
      <div className="flex flex-col">
        <span className="text-xs md:text-sm font-bold text-slate-900 leading-tight">{displayName}</span>
        <div className="flex items-center gap-2 md:gap-3 text-[10px] md:text-xs font-bold mt-0.5">
          <span className="flex items-center gap-1 text-slate-500">
            <Trophy size={10} className="text-blue-500 md:w-3 md:h-3" />
            <KudosCounter value={totalKudos} /> K
          </span>
          <span className="flex items-center gap-1 text-slate-500">
            <Medal size={10} className="text-yellow-500 md:w-3 md:h-3" />
            #{position || '-'}
          </span>
        </div>
      </div>
    </Link>
  );
};
