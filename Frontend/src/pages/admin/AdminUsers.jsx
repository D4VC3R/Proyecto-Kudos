import React from 'react';
import { AnimatePresence } from 'framer-motion';
import { Users, ShieldAlert, Filter } from 'lucide-react';
// Componentes
import SectionHeader from '../../components/common/SectionHeader';
import FeedbackState from '../../components/common/FeedbackState';
import Modal from '../../components/common/Modal';
import ModalButtons from "../../components/common/Buttons/ModalButtons.jsx";
import SearchFilter from "../../components/common/SearchFilter.jsx";
import SelectFilter from "../../components/common/SelectFilter.jsx";
import Pagination from '../../components/common/Buttons/Pagination.jsx';
import AdminUserCard from "../../components/admin/AdminUserCard.jsx";
import AdminUserBanBody from "../../components/admin/modals/AdminUserBanBody.jsx";
import AdminUserRevokeBody from "../../components/admin/modals/AdminUserRevokeBody.jsx";
// Hooks
import { useAdminUsersPage } from '../../hooks/pages/useAdminUsersPage.js';

const AdminUsers = () => {
  const { state, actions } = useAdminUsersPage();

  return (
    <div className="flex flex-col gap-6 animate-fade-in relative">
      <SectionHeader title="Gestión de" highlight="Usuarios" icon={Users}>
        <div className="flex gap-2 flex-wrap">
          <SelectFilter
            icon={Filter}
            value={state.filters.isBanned}
            onChange={(e) => actions.handleFilterChange('isBanned', e.target.value)}
            defaultOption="Todos los estados"
            options={[
              { value: '0', label: 'Activos' },
              { value: '1', label: 'Baneados' }
            ]}
          />
          <SearchFilter
            value={state.searchInput}
            onChange={actions.handleSearchChange}
            placeholder="Buscar por email o nombre..."
            maxWidth="max-w-sm"
          />
        </div>
      </SectionHeader>

      {state.isLoading ? (
        <FeedbackState icon={Users} isLoading title="Cargando usuarios..."/>
      ) : state.isError ? (
        <FeedbackState icon={ShieldAlert} title="Error" description="No se pudieron cargar los usuarios. Verifica tus permisos." iconColorClass="bg-red-100 text-red-500"/>
      ) : !state.usersResponse?.data?.length ? (
        <FeedbackState icon={Users} title="Sin resultados" description="No se encontraron usuarios con esos filtros."/>
      ) : (
        <div className="flex flex-col gap-4">
          <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
            <AnimatePresence>
              {state.usersResponse.data.map(user => (
                <AdminUserCard
                  key={user.id}
                  user={user}
                  onToggleBan={() => actions.handleToggleBan(user)}
                  onRevoke={() => actions.handleOpenAction(user, 'revoke')}
                  isBanning={state.isBanning}
                  isUnbanning={state.isUnbanning}
                  isRevoking={state.isRevoking}
                />
              ))}
            </AnimatePresence>
          </div>
          <Pagination meta={state.usersResponse.meta} onPageChange={actions.setPage}/>
        </div>
      )}

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

export default AdminUsers;