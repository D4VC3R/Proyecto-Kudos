import React from 'react';
import { User as UserIcon } from 'lucide-react';
import StorageImage from '../ui/StorageImage.jsx';
import { hasText } from '../../lib/text.js';

// Cabecera del perfil público (F3-03): avatar, nombre, @usuario y biografía. `children` va a la derecha (menú "Reportar").
const PublicProfileHeader = ({ profile, children }) => (
  <div className="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
    <div className="flex min-w-0 items-center gap-4 sm:gap-6">
      <div className="flex h-24 w-24 shrink-0 items-center justify-center overflow-hidden rounded-full bg-blue-100 text-primary shadow-lg ring-4 ring-white sm:h-32 sm:w-32">
        <StorageImage
          src={profile.avatar}
          alt={profile.name || 'Avatar del usuario'}
          className="w-full h-full"
          fallbackIcon={UserIcon}
        />
      </div>
      <div className="min-w-0">
        <h1 className="break-words text-3xl sm:text-4xl font-black text-text-highlight">{profile.name}</h1>
        <p className="break-all text-text-normal font-bold">@{profile.username}</p>
        {hasText(profile.biography) && (
          <p className="mt-3 max-w-2xl whitespace-pre-line text-text-normal">{profile.biography}</p>
        )}
      </div>
    </div>
    {children && <div className="relative shrink-0">{children}</div>}
  </div>
);

export default PublicProfileHeader;
