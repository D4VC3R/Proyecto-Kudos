import React from 'react';
import clsx from 'clsx';
import { User as UserIcon } from 'lucide-react';
import { twMerge } from '../../lib/twMerge.js';
import { hasText } from '../../lib/text.js';
import Card from '../ui/Card.jsx';
import Chip from '../ui/Chip.jsx';
import KudosBadge from '../ui/KudosBadge.jsx';
import StorageImage from '../ui/StorageImage.jsx';

// La misma tarjeta en /profile y en el perfil público, donde el nombre es el h1.
const ProfileIdentityCard = ({ name, username, avatar, biography, email, kudos, isAdmin, headingAs: Heading = 'h2', className, children }) => (
  <Card data-identity-card className={twMerge(clsx('flex flex-col gap-4 p-6', className))}>
    <div className="flex min-w-0 items-center gap-4">
      <div className="h-20 w-20 shrink-0 overflow-hidden rounded-full border border-line bg-canvas text-ink-muted">
        <StorageImage src={avatar} alt="" className="h-full w-full" fallbackIcon={UserIcon} />
      </div>
      <div className="min-w-0">
        <Heading className="break-words font-display text-h2 text-ink">{name}</Heading>
        {username && <p className="text-small text-ink-muted break-all">@{username}</p>}
      </div>
    </div>

    {email && <p className="text-small text-ink-muted break-all">{email}</p>}

    {(kudos != null || isAdmin) && (
      <div className="flex flex-wrap items-center gap-2">
        {kudos != null && <KudosBadge value={kudos} size="lg" />}
        {isAdmin && <Chip>Admin</Chip>}
      </div>
    )}

    {hasText(biography) && <p className="whitespace-pre-line text-ink">{biography}</p>}

    {children && <div className="relative flex flex-wrap items-center gap-3">{children}</div>}
  </Card>
);

export default ProfileIdentityCard;
