import React, { useState } from 'react';
import { useParams, useNavigate } from 'react-router-dom';
import { useAdminUserDetail } from '../../hooks/admin/useAdminUserQueries';
import { useBanUser, useUnbanUser, useRevokeUserSessions } from '../../hooks/admin/useAdminUserMutations';
import { SectionHeader } from '../../components/common/SectionHeader';
import { FeedbackState } from '../../components/common/FeedbackState';
import { Modal } from '../../components/common/Modal';
import { AdminUserBanBody } from '../../components/admin/AdminUserBanBody';
import { AdminUserRevokeBody } from '../../components/admin/AdminUserRevokeBody';
import { ModalButtons } from '../../components/common/ModalButtons';
import { ArrowLeft, UserSquare, ShieldAlert } from 'lucide-react';
import { AdminUserIdCard } from '../../components/admin/AdminUserIdCard';
import { AdminUserStatsPanel } from '../../components/admin/AdminUserStatsPanel';
import { AdminUserAdvancedDetails } from '../../components/admin/AdminUserAdvancedDetails';
import { useModal } from '../../hooks/useModal';
import { Button } from '../../components/common/Button';

const AdminUserDetail = () => {
  const { userId } = useParams();
  const navigate = useNavigate();
  const { data: user, isLoading, isError } = useAdminUserDetail(userId);
  const { isOpen, modalType, openModal, closeModal } = useModal();
  const [banParams, setBanParams] = useState({ reason: '', days: 0, is_permanent: false });
  const { mutate: banUser, isPending: isBanning } = useBanUser();
  const { mutate: unbanUser, isPending: isUnbanning } = useUnbanUser();
  const { mutate: revokeTokens, isPending: isRevoking } = useRevokeUserSessions();

  const executeAction = () => {
    if (modalType === 'ban') {
      banUser({
        userId: user.id,
        reason: banParams.reason,
        days: banParams.is_permanent ? null : banParams.days,
        is_permanent: banParams.is_permanent
      }, { onSuccess: closeModal });
    } else if (modalType === 'revoke') {
      revokeTokens(user.id, { onSuccess: closeModal });
    }
  };
  const handleToggleBan = () => {
    if (user?.is_banned) {
      unbanUser(user.id);
    } else {
      openModal('ban');
      setBanParams({ reason: '', days: 0, is_permanent: false });
    }
  };
  if (isLoading) return <FeedbackState icon={UserSquare} isLoading title="Cargando perfil de usuario..." />;
  if (isError || !user) return <FeedbackState icon={ShieldAlert} title="Error" description="No se pudo cargar la información del usuario." iconColorClass="bg-red-100 text-red-500" />;
  return (
    <div className="flex flex-col gap-6 animate-fade-in relative">
      <SectionHeader title="Detalles del" highlight="Usuario" icon={UserSquare}>
        <Button
          onClick={() => navigate('/admin/users')}
          variant="ghost"
          color="primary"
          size="sm"
          icon={ArrowLeft}
        >
          Volver a usuarios
        </Button>
      </SectionHeader>
      <div className="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <AdminUserIdCard
          user={user}
          isBanning={isBanning}
          isUnbanning={isUnbanning}
          isRevoking={isRevoking}
          onToggleBan={handleToggleBan}
          onRevokeSessions={() => openModal('revoke')}
        />
        <div className="lg:col-span-2 flex flex-col gap-6">
          <AdminUserStatsPanel user={user} />
          <AdminUserAdvancedDetails user={user} />
        </div>
      </div>
      <Modal
        isOpen={isOpen}
        onClose={closeModal}
        title={modalType === 'ban' ? 'Suspender Usuario' : 'Revocar Sesiones'}
        footer={
          <ModalButtons
            onClose={closeModal}
            onConfirm={executeAction}
            isPending={isBanning || isRevoking}
            confirmText="Confirmar Acción"
            actionStyle={modalType === 'ban' ? 'danger' : 'warning'}
          />
        }
      >
        {modalType === 'ban' ? (
          <AdminUserBanBody userName={user?.name} banParams={banParams} setBanParams={setBanParams} />
        ) : modalType === 'revoke' ? (
          <AdminUserRevokeBody userName={user?.name} />
        ) : null}
      </Modal>
    </div>
  );
};
export default AdminUserDetail;
