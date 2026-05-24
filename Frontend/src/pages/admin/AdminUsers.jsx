import React from 'react';
import {useAdminUsers} from '../../hooks/admin/useAdminUserQueries';
import {SectionHeader} from '../../components/common/SectionHeader';
import {FeedbackState} from '../../components/common/FeedbackState';
import {Modal} from '../../components/common/Modal';
import {Pagination} from '../../components/common/Pagination';
import {Users, ShieldAlert, Filter} from 'lucide-react';
import {AnimatePresence} from 'framer-motion';
import {AdminUserCard} from "../../components/admin/AdminUserCard.jsx";
import {ModalButtons} from "../../components/common/ModalButtons.jsx";
import {AdminUserBanBody} from "../../components/admin/AdminUserBanBody.jsx";
import {AdminUserRevokeBody} from "../../components/admin/AdminUserRevokeBody.jsx";
import {SearchFilter} from "../../components/common/SearchFilter.jsx";
import {SelectFilter} from "../../components/common/SelectFilter.jsx";
import {useFilters} from '../../hooks/common/useFilters';
import {useAdminUserActions} from '../../hooks/admin/useAdminUserActions';

const AdminUsers = () => {
  const {
    page, setPage, searchInput, debouncedSearch, handleSearchChange, filters, handleFilterChange
  } = useFilters({initialFilters: {isBanned: ''}});

  const queryFilters = {page, search: debouncedSearch, per_page: 24};
  if (filters.isBanned !== '') {
    queryFilters.is_banned = filters.isBanned === '1' ? 1 : 0;
  }

  const {data: usersResponse, isLoading, isError} = useAdminUsers(queryFilters);

  const {
    isOpen, modalType, selectedUser, closeModal,
    handleOpenAction, handleToggleBan, executeAction,
    isPending, isBanning, isUnbanning, isRevoking,
    banParams, setBanParams
  } = useAdminUserActions();

  return (
    <div className="flex flex-col gap-6 animate-fade-in relative">
      <SectionHeader title="Gestión de" highlight="Usuarios" icon={Users}>
        <div className="flex gap-2 flex-wrap">
          <SelectFilter
            icon={Filter}
            value={filters.isBanned}
            onChange={(e) => handleFilterChange('isBanned', e.target.value)}
            defaultOption="Todos los estados"
            options={[
              {value: '0', label: 'Activos'},
              {value: '1', label: 'Baneados'}
            ]}
          />
          <SearchFilter value={searchInput} onChange={handleSearchChange} placeholder="Buscar por email o nombre..."
                        maxWidth="max-w-sm"/>
        </div>
      </SectionHeader>

      {isLoading ? (
        <FeedbackState icon={Users} isLoading title="Cargando usuarios..."/>
      ) : isError ? (
        <FeedbackState icon={ShieldAlert} title="Error"
                       description="No se pudieron cargar los usuarios. Verifica tus permisos."
                       iconColorClass="bg-red-100 text-red-500"/>
      ) : !usersResponse?.data?.length ? (
        <FeedbackState icon={Users} title="Sin resultados" description="No se encontraron usuarios con esos filtros."/>
      ) : (
        <div className="flex flex-col gap-4">
          <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
            <AnimatePresence>
              {usersResponse.data.map(user => (
                <AdminUserCard
                  key={user.id}
                  user={user}
                  onToggleBan={() => handleToggleBan(user)}
                  onRevoke={() => handleOpenAction(user, 'revoke')}
                  isBanning={isBanning}
                  isUnbanning={isUnbanning}
                  isRevoking={isRevoking}
                />
              ))}
            </AnimatePresence>
          </div>
          <Pagination meta={usersResponse.meta} onPageChange={setPage}/>
        </div>
      )}

      <Modal
        isOpen={isOpen}
        onClose={closeModal}
        title={modalType === 'ban' ? 'Suspender usuario' : 'Revocar sesiones'}
        footer={<ModalButtons onClose={closeModal} onConfirm={executeAction} isPending={isPending}
                              confirmText="Confirmar Acción" actionStyle={modalType === 'ban' ? 'danger' : 'warning'}/>}
      >
        {modalType === 'ban' ? (
          <AdminUserBanBody
            userName={selectedUser?.name}
            banParams={banParams}
            setBanParams={setBanParams}
          />
        ) : modalType === 'revoke' ? (
          <AdminUserRevokeBody userName={selectedUser?.name}/>
        ) : null}
      </Modal>
    </div>
  );
};
export default AdminUsers;