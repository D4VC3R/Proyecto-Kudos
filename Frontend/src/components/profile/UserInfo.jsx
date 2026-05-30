import React from 'react';
import { Link } from 'react-router-dom';
import { UserCircle, Trophy, Medal } from 'lucide-react';
// Componentes
import KudosCounter from '../animations/KudosCounter.jsx';
import StorageImage from '../ui/StorageImage.jsx';
import AnimatedItem from "../animations/AnimatedItem.jsx";
// Hooks
import { useSessionStore } from '../../store/useSessionStore.js';
import { useMinimalProfile } from '../../hooks/users/useUserQueries.js';

const UserInfo = () => {
  const user = useSessionStore((state) => state.user);
  const { data: profile } = useMinimalProfile();

  return (
    <AnimatedItem>
      <Link
        to="/profile"
        className="flex items-center gap-2 sm:gap-3 bg-background hover:bg-blue-50 transition-colors border border-border rounded-full pr-3 sm:pr-4 pl-1 py-1"
      >
        <div className="w-8 h-8 sm:w-10 sm:h-10 lg:w-11 lg:h-11 rounded-full bg-blue-100 flex items-center justify-center text-primary overflow-hidden shrink-0">
          <StorageImage
            src={profile?.avatar}
            alt={user?.name || 'Avatar'}
            className="w-full h-full"
            fallbackIcon={UserCircle}
          />
        </div>

        <div className="flex flex-col justify-center min-w-0">
          <span className="text-[11px] sm:text-xs lg:text-sm font-bold text-text-highlight leading-tight truncate max-w-[90px] sm:max-w-[140px] lg:max-w-none">
            {user?.name}
          </span>

          <div className="flex items-center gap-2 sm:gap-3 text-[9px] sm:text-[10px] lg:text-xs font-bold mt-0.5">
            <span className="flex items-center gap-0.5 sm:gap-1 text-text-normal whitespace-nowrap">
              <Trophy className="text-blue-500 w-2.5 h-2.5 sm:w-4 sm:h-4" />
              <KudosCounter value={user?.total_kudos ?? 0} /> K
            </span>
            <span className="flex items-center gap-0.5 sm:gap-1 text-text-normal whitespace-nowrap">
              <Medal className="text-yellow-500 w-2.5 h-2.5 sm:w-4 sm:h-4" />
              #{profile?.ranking_position || '-'}
            </span>
          </div>
        </div>
      </Link>
    </AnimatedItem>
  );
};

export default UserInfo;