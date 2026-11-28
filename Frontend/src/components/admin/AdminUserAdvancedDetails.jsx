import React from 'react';
import SectionHead from '../ui/SectionHead.jsx';
import Card from '../ui/Card.jsx';
import { formatDate } from '../../lib/formatters.js';

const DT = 'text-caption font-semibold uppercase text-ink-muted';
const DD = 'text-small text-ink';

const AdminUserAdvancedDetails = ({ user }) => {
  return (
    <>
      <SectionHead as="h3" title="Detalles avanzados"/>
      <Card>
        <dl className="grid grid-cols-1 gap-x-8 gap-y-6 sm:grid-cols-2">
          <div className="flex flex-col">
            <dt className={DT}>ID Usuario</dt>
            <dd className={`${DD} break-all`}>{user.id}</dd>
          </div>
          <div className="flex flex-col">
            <dt className={DT}>Email verificado</dt>
            <dd className={DD}>
              {user.is_verified ? 'Sí' : 'No'}
              {user.email_verified_at && <span className="ml-2 text-ink-muted">({formatDate(user.email_verified_at)})</span>}
            </dd>
          </div>
          <div className="flex flex-col">
            <dt className={DT}>Ciudad</dt>
            <dd className={DD}>{user.profile?.city || 'No especificada'}</dd>
          </div>
          <div className="flex flex-col">
            <dt className={DT}>Fecha de nacimiento</dt>
            <dd className={DD}>
              {user.profile?.birthdate ? formatDate(user.profile.birthdate) : 'No especificada'}
            </dd>
          </div>
        </dl>
      </Card>
    </>
  );
};

export default AdminUserAdvancedDetails;
