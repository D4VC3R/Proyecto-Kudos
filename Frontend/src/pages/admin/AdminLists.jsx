import React from 'react';
import { ListOrdered, Plus, ShieldAlert } from 'lucide-react';
import { AnimatePresence } from 'framer-motion';
// Componentes
import SectionHeader from '../../components/ui/SectionHeader.jsx';
import FeedbackState from '../../components/ui/FeedbackState.jsx';
import Button from '../../components/ui/Button.jsx';
import Modal from '../../components/ui/Modal.jsx';
import ModalButtons from '../../components/ui/ModalButtons.jsx';
import SearchFilter from '../../components/ui/SearchFilter.jsx';
import AdminListCard from '../../components/admin/AdminListCard.jsx';
import AdminListFormBody from '../../components/admin/modals/AdminListFormBody.jsx';
import AdminListDeleteBody from '../../components/admin/modals/AdminListDeleteBody.jsx';
// Hooks
import { useAdminListsPage } from '../../hooks/pages/useAdminListsPage.js';

const MODAL_TITLES = { create: 'Nueva lista', edit: 'Editar lista', delete: 'Eliminar lista' };

const AdminLists = () => {
  const { state, actions } = useAdminListsPage();

  return (
    <div className="flex flex-col gap-6 animate-fade-in relative">
      <SectionHeader title="Listas" highlight="editoriales" highlightColor="accent" icon={ListOrdered}>
        <div className="flex gap-4 items-center flex-wrap">
          <SearchFilter
            value={state.searchInput}
            onChange={actions.handleSearchChange}
            placeholder="Buscar lista..."
          />
          <Button
            onClick={() => actions.handleOpenAction(null, 'create')}
            variant="ring"
            color="primary"
            icon={Plus}
          >
            Nueva
          </Button>
        </div>
      </SectionHeader>

      {state.isLoading ? (
        <FeedbackState icon={ListOrdered} isLoading title="Cargando listas..."/>
      ) : state.isError ? (
        <FeedbackState icon={ShieldAlert} title="Error" description="No se pudieron cargar las listas." iconColorClass="bg-red-100 text-red-500"/>
      ) : !state.filteredLists?.length ? (
        <FeedbackState icon={ListOrdered} title="Sin resultados" description="No hay listas editoriales."/>
      ) : (
        <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
          <AnimatePresence>
            {state.filteredLists.map((list) => (
              <AdminListCard
                key={list.id}
                list={list}
                onEdit={() => actions.handleOpenAction(list, 'edit')}
                onDelete={() => actions.handleOpenAction(list, 'delete')}
              />
            ))}
          </AnimatePresence>
        </div>
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
