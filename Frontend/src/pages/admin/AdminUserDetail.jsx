import React from 'react';
import { useParams, useNavigate } from 'react-router-dom';
import { useAdminUserDetail } from '../../hooks/admin/useAdminUserQueries';
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
import { Button } from '../../components/common/Button';
import { useAdminUserActions } from '../../hooks/admin/useAdminUserActions';

const AdminUserDetail = () => {
  const { userId } = useParams();
  const navigate = useNavigate();
  const { data: user, isLoading, isError } = useAdminUserDetail(userId);

  const {
    isOpen, modalType, selectedUser, closeModal,
    handleOpenAction, handleToggleBan, executeAction,
    isPending, isBanning, isUnbanning, isRevoking,
    banParams, setBanParams
  } = useAdminUserActions();

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
          onToggleBan={() => handleToggleBan(user)}
          onRevokeSessions={() => handleOpenAction(user, 'revoke')}
        />
        <div className="lg:col-span-2 flex flex-col gap-6">
          <AdminUserStatsPanel user={user} />
          <AdminUserAdvancedDetails user={user} />
        </div>
      </div>

      <Modal
        isOpen={isOpen}
        onClose={closeModal}
        title={modalType === 'ban' ? 'Suspender usuario' : 'Revocar sesiones'}
        footer={
          <ModalButtons
            onClose={closeModal}
            onConfirm={executeAction}
            isPending={isPending}
            confirmText="Confirmar Acción"
            actionStyle={modalType === 'ban' ? 'danger' : 'warning'}
          />
        }
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

export default AdminUserDetail;