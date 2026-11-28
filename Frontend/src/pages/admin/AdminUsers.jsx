import React from 'react';
import { Users, ShieldAlert, Filter, Shield, CheckCircle, Search } from 'lucide-react';
// Componentes
import SectionHead from '../../components/ui/SectionHead.jsx';
import FeedbackState from '../../components/ui/FeedbackState.jsx';
import FilterBar from '../../components/ui/FilterBar.jsx';
import Input from '../../components/ui/Input.jsx';
import Select from '../../components/ui/Select.jsx';
import Modal from '../../components/ui/Modal.jsx';
import ModalButtons from "../../components/ui/ModalButtons.jsx";
import Pagination from '../../components/ui/Pagination.jsx';
import AdminUsersTable from "../../components/admin/AdminUsersTable.jsx";
import AdminUserBanBody from "../../components/admin/modals/AdminUserBanBody.jsx";
import AdminUserRevokeBody from "../../components/admin/modals/AdminUserRevokeBody.jsx";
// Hooks
import { useAdminUsersPage } from '../../hooks/pages/useAdminUsersPage.js';

const AdminUsers = () => {
  const { state, actions } = useAdminUsersPage();

  return (
    <div className="flex flex-col gap-6 animate-fade-in relative">
      <SectionHead title="Gestión de usuarios"/>

      <FilterBar label="Filtrar usuarios">
        <Select
          label="Estado de la cuenta"
          icon={Filter}
          value={state.filters.isBanned}
          onChange={(e) => actions.handleFilterChange('isBanned', e.target.value)}
          placeholder="Todos"
          selectClassName="w-auto"
          options={[
            { value: '0', label: 'Activos' },
            { value: '1', label: 'Baneados' }
          ]}
        />
        <Select
          label="Rol"
          icon={Shield}
          value={state.filters.role}
          onChange={(e) => actions.handleFilterChange('role', e.target.value)}
          placeholder="Todos"
          selectClassName="w-auto"
          options={[
            { value: 'admin', label: 'Administradores' },
            { value: 'user', label: 'Usuarios' }
          ]}
        />
        <Select
          label="Verificación del email"
          icon={CheckCircle}
          value={state.filters.isVerified}
          onChange={(e) => actions.handleFilterChange('isVerified', e.target.value)}
          placeholder="Todos"
          selectClassName="w-auto"
          options={[
            { value: '1', label: 'Verificados' },
            { value: '0', label: 'No verificados' }
          ]}
        />
        <Input
          type="search"
          label="Buscar"
          icon={Search}
          className="w-full sm:w-64"
          value={state.searchInput}
          onChange={actions.handleSearchChange}
          placeholder="Buscar..."
        />
      </FilterBar>

      {state.isLoading ? (
        <FeedbackState icon={Users} isLoading titleAs="h3" title="Cargando usuarios..."/>
      ) : state.isError ? (
        <FeedbackState icon={ShieldAlert} titleAs="h3" title="Error" description="No se pudieron cargar los usuarios. Verifica tus permisos." iconColorClass="bg-danger/10 text-danger"/>
      ) : !state.usersResponse?.data?.length ? (
        <FeedbackState icon={Users} titleAs="h3" title="Sin resultados" description="No se encontraron usuarios con esos filtros."/>
      ) : (
        <div className="flex flex-col gap-4">
          <AdminUsersTable
            users={state.usersResponse.data}
            onToggleBan={(user) => actions.handleToggleBan(user)}
            onRevoke={(user) => actions.handleOpenAction(user, 'revoke')}
            isBanning={state.isBanning}
            isUnbanning={state.isUnbanning}
            isRevoking={state.isRevoking}
          />
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
