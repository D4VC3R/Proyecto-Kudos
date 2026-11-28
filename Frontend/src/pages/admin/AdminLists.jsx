import React from 'react';
import { ListOrdered, Plus, Search, ShieldAlert } from 'lucide-react';
// Componentes
import SectionHead from '../../components/ui/SectionHead.jsx';
import FeedbackState from '../../components/ui/FeedbackState.jsx';
import FilterBar from '../../components/ui/FilterBar.jsx';
import Input from '../../components/ui/Input.jsx';
import Button from '../../components/ui/Button.jsx';
import Modal from '../../components/ui/Modal.jsx';
import ModalButtons from '../../components/ui/ModalButtons.jsx';
import AdminListsTable from '../../components/admin/AdminListsTable.jsx';
import AdminListFormBody from '../../components/admin/modals/AdminListFormBody.jsx';
import AdminListDeleteBody from '../../components/admin/modals/AdminListDeleteBody.jsx';
// Hooks
import { useAdminListsPage } from '../../hooks/pages/useAdminListsPage.js';

const MODAL_TITLES = { create: 'Nueva lista', edit: 'Editar lista', delete: 'Eliminar lista' };

const AdminLists = () => {
  const { state, actions } = useAdminListsPage();

  return (
    <div className="flex flex-col gap-6 animate-fade-in relative">
      <SectionHead title="Listas editoriales">
        <Button onClick={() => actions.handleOpenAction(null, 'create')} icon={Plus}>
          Nueva
        </Button>
      </SectionHead>

      <FilterBar label="Filtrar listas">
        <Input
          type="search"
          label="Buscar"
          icon={Search}
          className="w-full sm:w-64"
          value={state.searchInput}
          onChange={actions.handleSearchChange}
          placeholder="Buscar lista..."
        />
      </FilterBar>

      {state.isLoading ? (
        <FeedbackState icon={ListOrdered} isLoading titleAs="h3" title="Cargando listas..."/>
      ) : state.isError ? (
        <FeedbackState icon={ShieldAlert} titleAs="h3" title="Error" description="No se pudieron cargar las listas." iconColorClass="bg-danger/10 text-danger"/>
      ) : !state.filteredLists?.length ? (
        <FeedbackState icon={ListOrdered} titleAs="h3" title="Sin resultados" description="No hay listas editoriales."/>
      ) : (
        <AdminListsTable
          lists={state.filteredLists}
          onEdit={(list) => actions.handleOpenAction(list, 'edit')}
          onDelete={(list) => actions.handleOpenAction(list, 'delete')}
        />
      )}

      <Modal
        isOpen={state.isOpen}
        onClose={actions.handleCloseModal}
        title={MODAL_TITLES[state.modalType] ?? ''}
        footer={
          <ModalButtons
            onClose={actions.handleCloseModal}
            onConfirm={actions.executeAction}
            isPending={state.isPending}
            actionStyle={state.modalType === 'delete' ? 'danger' : 'primary'}
          />
        }
      >
        <div className="flex flex-col gap-4">
          {state.modalType === 'delete' ? (
            <AdminListDeleteBody listTitle={state.selectedList?.title}/>
          ) : (
            <AdminListFormBody formData={state.formData} setFormData={actions.setFormData}/>
          )}
        </div>
      </Modal>
    </div>
  );
};

export default AdminLists;
