import React, { useState } from 'react';
import { useAdminUsers } from '../../hooks/admin/useAdminUserQueries';
import { useBanUser, useUnbanUser, useRevokeUserSessions } from '../../hooks/admin/useAdminUserMutations';
import { SectionHeader } from '../../components/common/SectionHeader';
import { FeedbackState } from '../../components/common/FeedbackState';
import { Modal } from '../../components/common/Modal';
import { Pagination } from '../../components/common/Pagination';
import { Users, ShieldAlert, Filter } from 'lucide-react';
import { AnimatePresence } from 'framer-motion';
import {AdminUserCard} from "../../components/admin/AdminUserCard.jsx";
import {ModalButtons} from "../../components/common/ModalButtons.jsx";
import {AdminUserBanBody} from "../../components/admin/AdminUserBanBody.jsx";
import {AdminUserRevokeBody} from "../../components/admin/AdminUserRevokeBody.jsx";
import {SearchFilter} from "../../components/common/SearchFilter.jsx";
import {SelectFilter} from "../../components/common/SelectFilter.jsx";

const AdminUsers = () => {
  const [page, setPage] = useState(1);
  const [search, setSearch] = useState('');
  const [isBanned, setIsBanned] = useState('');
  const [selectedUser, setSelectedUser] = useState(null);
  const [modalType, setModalType] = useState(null);
  const [banParams, setBanParams] = useState({ reason: '', days: 0, is_permanent: false });

  const filters = { page, search, per_page: 24 };
  if (isBanned !== '') filters.is_banned = isBanned === '1' ? 1 : 0;

  const { data: usersResponse, isLoading, isError } = useAdminUsers(filters);
  const { mutate: banUser, isPending: isBanning } = useBanUser();
  const { mutate: unbanUser, isPending: isUnbanning } = useUnbanUser();
  const { mutate: revokeTokens, isPending: isRevoking } = useRevokeUserSessions();
  const handleSearch = (e) => {
    setSearch(e.target.value);
    setPage(1);
  };
  const handleOpenAction = (user, type) => {
    setSelectedUser(user);
    setModalType(type);
    if (type === 'ban') setBanParams({ reason: '', days: 0, is_permanent: false });
  };
  const handleCloseModal = () => {
    setSelectedUser(null);
    setModalType(null);
  };
  const executeAction = () => {
    if (!selectedUser) return;
    if (modalType === 'ban') {
      banUser({
        userId: selectedUser.id,
        reason: banParams.reason,
        days: banParams.days,
        is_permanent: banParams.is_permanent
      }, { onSuccess: handleCloseModal });
    } else if (modalType === 'revoke') {
      revokeTokens(selectedUser.id, { onSuccess: handleCloseModal });
    }
  };
  const handleToggleBan = (user) => {
    if (user.is_banned) {
      unbanUser(user.id);
    } else {
      handleOpenAction(user, 'ban');
    }
  };
  return (
    <div className="flex flex-col gap-6 animate-fade-in relative">
      <SectionHeader title="Gestión de" highlight="Usuarios" icon={Users}>
        <div className="flex gap-2 flex-wrap">
          <SelectFilter
            icon={Filter}
            value={isBanned}
            onChange={(e) => { setIsBanned(e.target.value); setPage(1); }}
            defaultOption="Todos los estados"
            options={[
              { value: '0', label: 'Activos' },
              { value: '1', label: 'Baneados' }
            ]}
          />
          <SearchFilter
            value={search}
            onChange={handleSearch}
            placeholder="Buscar por email o nombre..."
            maxWidth="max-w-sm"
          />
        </div>
      </SectionHeader>
      {isLoading ? (
        <FeedbackState icon={Users} isLoading title="Cargando usuarios..." />
      ) : isError ? (
        <FeedbackState icon={ShieldAlert} title="Error" description="No se pudieron cargar los usuarios. Verifica tus permisos." iconColorClass="bg-red-100 text-red-500" />
      ) : !usersResponse?.data?.length ? (
        <FeedbackState icon={Users} title="Sin resultados" description="No se encontraron usuarios con esos filtros." />
      ) : (
        <div className="flex flex-col gap-4">
          <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
            <AnimatePresence>
              {usersResponse.data.map(user => (
                <AdminUserCard
                  key={user.id}
                  user={user}
                  onToggleBan={handleToggleBan}
                  onRevoke={() => handleOpenAction(user, 'revoke')}
                  isBanning={isBanning}
                  isUnbanning={isUnbanning}
                  isRevoking={isRevoking}
                />
              ))}
            </AnimatePresence>
          </div>
          <Pagination meta={usersResponse.meta} onPageChange={setPage} />
        </div>
      )}
      {/* Modal Actions */}
      <Modal
        isOpen={!!modalType}
        onClose={handleCloseModal}
        title={modalType === 'ban' ? 'Suspender Usuario' : 'Revocar Sesiones'}
        footer={<ModalButtons onClose={handleCloseModal} onConfirm={executeAction} isPending={isBanning || isRevoking} confirmText="Confirmar Acción" actionStyle={modalType === 'ban' ? 'danger' : 'warning'} />}
      >
        {modalType === 'ban' ? (
          <AdminUserBanBody
            userName={selectedUser?.name}
            banParams={banParams}
            setBanParams={setBanParams}
          />
        ) : modalType === 'revoke' ? (
          <AdminUserRevokeBody userName={selectedUser?.name} />
        ) : null}
      </Modal>
    </div>
  );
};
export default AdminUsers;
