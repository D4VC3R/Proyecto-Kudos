import React from 'react';
import { User as UserIcon } from 'lucide-react';

export const ProfileSummaryCard = ({ user, profile }) => {

  const STORAGE_URL = "http://localhost:8095/storage/";
  const avatarUrl = profile?.avatar ? `${STORAGE_URL}${profile.avatar}` : null;

  return (
    <div className="flex flex-col items-center p-6 bg-slate-50 rounded-3xl border border-slate-200 w-full">
      <div className="w-32 h-32 rounded-full overflow-hidden bg-blue-100 text-blue-600 flex items-center justify-center mb-4 ring-4 ring-white shadow-lg">
        {avatarUrl ? (
          <img src={avatarUrl} alt={user?.name} className="w-full h-full object-cover" />
        ) : (
          <UserIcon size={64} />
        )}
      </div>

      <h2 className="text-2xl font-black text-slate-900 text-center">{user?.name}</h2>
      <p className="text-slate-500 font-medium mb-4">{user?.email}</p>

      <div className="flex flex-wrap justify-center gap-2">
        <span className="px-3 py-1 bg-blue-100 text-blue-700 text-xs font-bold rounded-full">
          {profile?.total_kudos || user?.total_kudos || 0} Kudos
        </span>
        {user?.role === 'admin' && (
          <span className="px-3 py-1 bg-purple-100 text-purple-700 text-xs font-bold rounded-full">
            Admin
          </span>
        )}
      </div>
    </div>
  );
};