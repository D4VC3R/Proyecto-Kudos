import React from 'react';
import { User as UserIcon } from 'lucide-react';
import StorageImage from "../common/StorageImage.jsx";

export const ProfileSummaryCard = ({ user, profile }) => {
  return (
    <div className="flex flex-col items-center p-6 bg-background rounded-3xl border border-border w-full">
      <div className="w-32 h-32 rounded-full overflow-hidden bg-blue-100 text-primary flex items-center justify-center mb-4 ring-4 ring-white shadow-lg">
        <StorageImage
          src={profile?.avatar}
          alt={user?.name || 'Avatar del usuario'}
          className="w-full h-full"
          fallbackIcon={UserIcon}
        />
      </div>

      <h2 className="text-2xl font-black text-text-highlight text-center">{user?.name}</h2>
      <p className="text-text-normal font-medium mb-4">{user?.email}</p>

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