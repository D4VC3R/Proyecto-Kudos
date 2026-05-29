import React from 'react';
import { ArrowLeft, UserSquare, ShieldAlert } from 'lucide-react';
// Componentes
import SectionHeader from '../../components/common/SectionHeader';
import FeedbackState from '../../components/common/FeedbackState';
import Modal from '../../components/common/Modal';
import ModalButtons from '../../components/common/Buttons/ModalButtons.jsx';
import Button from '../../components/common/Buttons/Button.jsx';
import AdminUserBanBody from '../../components/admin/modals/AdminUserBanBody.jsx';
import AdminUserRevokeBody from '../../components/admin/modals/AdminUserRevokeBody.jsx';
import AdminUserIdCard from '../../components/admin/AdminUserIdCard';
import AdminUserStatsPanel from '../../components/admin/AdminUserStatsPanel';
import AdminUserAdvancedDetails from '../../components/admin/AdminUserAdvancedDetails';
// Hooks
import { useAdminUserDetailPage } from '../../hooks/pages/useAdminUserDetailPage.js';

const AdminUserDetail = () => {
  const { state, actions } = useAdminUserDetailPage();

  if (state.isLoading) return <FeedbackState icon={UserSquare} isLoading title="Cargando perfil de usuario..."/>;
  if (state.isError || !state.user) return <FeedbackState icon={ShieldAlert} title="Error" description="No se pudo cargar la información del usuario." iconColorClass="bg-red-100 text-red-500"/>;

  return (
    <div className="flex flex-col gap-6 animate-fade-in relative">
      <SectionHeader title="Detalles del" highlight="Usuario" icon={UserSquare}>
        <Button
          onClick={actions.handleGoBack}
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
          user={state.user}
          isBanning={state.isBanning}
          isUnbanning={state.isUnbanning}
          isRevoking={state.isRevoking}
          onToggleBan={() => actions.handleToggleBan(state.user)}
          onRevokeSessions={() => actions.handleOpenAction(state.user, 'revoke')}
        />
        <div className="lg:col-span-2 flex flex-col gap-6">
          <AdminUserStatsPanel user={state.user}/>
          <AdminUserAdvancedDetails user={state.user}/>
        </div>
      </div>

      <Modal
        isOpen={state.isOpen}
        onClose={actions.closeModal}
        title={state.modalType === 'ban' ? 'Suspender usuario' : 'Revocar sesiones'}
        footer={
          <ModalButtons
            onClose={actions.closeModal}
            onConfirm={actions.executeAction}
            isPending={state.isPending}
            confirmText="Confirmar Acción"
            actionStyle={state.modalType === 'ban' ? 'danger' : 'warning'}
          />
        }
      >
        {state.modalType === 'ban' ? (
          <AdminUserBanBody
            userName={state.selectedUser?.name}
            banParams={state.banParams}
            setBanParams={actions.setBanParams}
          />
        ) : state.modalType === 'revoke' ? (
          <AdminUserRevokeBody userName={state.selectedUser?.name}/>
        ) : null}
      </Modal>
    </div>
  );
};

export default AdminUserDetail;