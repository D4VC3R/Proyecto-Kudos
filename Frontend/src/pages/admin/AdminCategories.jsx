import React from 'react';
import { LayoutGrid, Plus, ShieldAlert } from 'lucide-react';
import { AnimatePresence } from 'framer-motion';
// Componentes
import SectionHeader from '../../components/common/SectionHeader';
import FeedbackState from '../../components/common/FeedbackState';
import Button from '../../components/common/Buttons/Button.jsx';
import Modal from '../../components/common/Modal';
import ModalButtons from "../../components/common/Buttons/ModalButtons.jsx";
import SearchFilter from '../../components/common/SearchFilter';
import AdminCategoryCard from "../../components/admin/AdminCategoryCard.jsx";
import AdminCategoryFormBody from "../../components/admin/modals/AdminCategoryFormBody.jsx";
import AdminCategoryDeleteBody from "../../components/admin/modals/AdminCategoryDeleteBody.jsx";
// Hooks
import { useAdminCategoriesPage } from '../../hooks/pages/useAdminCategoriesPage.js';

const AdminCategories = () => {
  const { state, actions } = useAdminCategoriesPage();

  return (
    <div className="flex flex-col gap-6 animate-fade-in relative">
      <SectionHeader title="Gestión de" highlight="Categorías" highlightColor="accent" icon={LayoutGrid}>
        <div className="flex gap-4 items-center flex-wrap">
          <SearchFilter
            value={state.searchInput}
            onChange={actions.handleSearchChange}
            placeholder="Buscar categoría..."
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
        <FeedbackState icon={LayoutGrid} isLoading title="Cargando categorías..."/>
      ) : state.isError ? (
        <FeedbackState icon={ShieldAlert} title="Error" description="No se pudieron cargar las categorías." iconColorClass="bg-red-100 text-red-500"/>
      ) : !state.filteredCategories?.length ? (
        <FeedbackState icon={LayoutGrid} title="Sin resultados" description="No se encontraron categorías."/>
      ) : (
        <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
          <AnimatePresence>
            {state.filteredCategories.map(cat => (
              <AdminCategoryCard
                key={cat.id}
                category={cat}
                onEdit={() => actions.handleOpenAction(cat, 'edit')}
                onDelete={() => actions.handleOpenAction(cat, 'delete')}
              />
            ))}
          </AnimatePresence>
        </div>
      )}

      <Modal
        isOpen={state.isOpen}
        onClose={actions.handleCloseModal}
        title={state.modalType === 'create' ? 'Nueva Categoría' : state.modalType === 'edit' ? 'Editar Categoría' : 'Eliminar Categoría'}
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
            <AdminCategoryDeleteBody categoryName={state.selectedCat?.name}/>
          ) : (
            <AdminCategoryFormBody formData={state.formData} setFormData={actions.setFormData}/>
          )}
        </div>
      </Modal>
    </div>
  );
};

export default AdminCategories;