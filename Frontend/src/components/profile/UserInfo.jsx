import React from 'react';
import {Link} from 'react-router-dom';
import {UserCircle, Trophy, Medal} from 'lucide-react';
// Componentes
import KudosCounter from '../animations/KudosCounter.jsx';
import StorageImage from '../ui/StorageImage.jsx';
import AnimatedItem from "../animations/AnimatedItem.jsx";
// Hooks
import {useSessionStore} from '../../store/useSessionStore.js';
import {useMinimalProfile} from '../../hooks/users/useUserQueries.js';

const UserInfo = () => {
  const user = useSessionStore((state) => state.user);
  const {data: profile} = useMinimalProfile();

  return (
    <AnimatedItem>
      <Link
        to="/profile"
        className="flex items-center gap-4 bg-background hover:bg-blue-50 transition-colors border border-border rounded-full pr-4 pl-1 py-1"
      >
        <div
          className="w-10 h-10 rounded-full bg-blue-100 flex items-center justify-center text-primary overflow-hidden shrink-0">
          <StorageImage
            src={profile?.avatar}
            alt={user?.name || 'Avatar'}
            className="w-full h-full"
            fallbackIcon={UserCircle}
          />
        </div>

        <div className="flex flex-col">
          <span className="text-xs md:text-sm font-bold text-text-highlight leading-tight">{user?.name}</span>
          <div className="flex items-center gap-2 md:gap-3 text-[10px] md:text-xs font-bold mt-0.5">
          <span className="flex items-center gap-1 text-text-normal">
            <Trophy size={10} className="text-blue-500 md:w-3 md:h-3"/>
            <KudosCounter value={user?.total_kudos ?? 0}/> K
          </span>
            <span className="flex items-center gap-1 text-text-normal">
            <Medal size={10} className="text-yellow-500 md:w-3 md:h-3"/>
            #{profile?.ranking_position || '-'}
          </span>
          </div>
        </div>
      </Link>
    </AnimatedItem>
  );
};

export default UserInfo;