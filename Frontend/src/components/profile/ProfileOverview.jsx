import React from 'react';
import { useSessionStore } from '../../store/useSessionStore';
import { FadeUp } from '../animations/FadeUp';
import { ProfileSummaryCard } from './ProfileSummaryCard';
import { ProfileEditForm } from './ProfileEditForm';

export const ProfileOverview = ({ profile }) => {
  const user = useSessionStore((state) => state.user);

  return (
    <FadeUp className="flex flex-col gap-6 md:flex-row w-full items-start">

      <div className="w-full md:w-1/3 lg:w-1/4 shrink-0">
        <ProfileSummaryCard user={user} profile={profile} />
      </div>

      <ProfileEditForm profile={profile} />

    </FadeUp>
  );
};