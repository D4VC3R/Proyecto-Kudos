import React from 'react';
import { LayoutGrid, Plus, Search, ShieldAlert } from 'lucide-react';
// Componentes
import SectionHead from '../../components/ui/SectionHead.jsx';
import FeedbackState from '../../components/ui/FeedbackState.jsx';
import FilterBar from '../../components/ui/FilterBar.jsx';
import Input from '../../components/ui/Input.jsx';
import Button from '../../components/ui/Button.jsx';
import Modal from '../../components/ui/Modal.jsx';
import ModalButtons from "../../components/ui/ModalButtons.jsx";
import AdminCategoriesTable from "../../components/admin/AdminCategoriesTable.jsx";
import AdminCategoryFormBody from "../../components/admin/modals/AdminCategoryFormBody.jsx";
import AdminCategoryDeleteBody from "../../components/admin/modals/AdminCategoryDeleteBody.jsx";
// Hooks
import { useAdminCategoriesPage } from '../../hooks/pages/useAdminCategoriesPage.js';

const AdminCategories = () => {
  const { state, actions } = useAdminCategoriesPage();

  return (
    <div className="flex flex-col gap-6 animate-fade-in relative">
      <SectionHead title="Gestión de categorías">
        <Button onClick={() => actions.handleOpenAction(null, 'create')} icon={Plus}>
          Nueva
        </Button>
      </SectionHead>

      <FilterBar label="Filtrar categorías">
        <Input
          type="search"
          label="Buscar"
          icon={Search}
          className="w-full sm:w-64"
          value={state.searchInput}
          onChange={actions.handleSearchChange}
          placeholder="Buscar categoría..."
        />
      </FilterBar>

      {state.isLoading ? (
        <FeedbackState icon={LayoutGrid} isLoading titleAs="h3" title="Cargando categorías..."/>
      ) : state.isError ? (
        <FeedbackState icon={ShieldAlert} titleAs="h3" title="Error" description="No se pudieron cargar las categorías." iconColorClass="bg-danger/10 text-danger"/>
      ) : !state.filteredCategories?.length ? (
        <FeedbackState icon={LayoutGrid} titleAs="h3" title="Sin resultados" description="No se encontraron categorías."/>
      ) : (
        <AdminCategoriesTable
          categories={state.filteredCategories}
          onEdit={(cat) => actions.handleOpenAction(cat, 'edit')}
          onDelete={(cat) => actions.handleOpenAction(cat, 'delete')}
        />
      )}

      <Modal
        isOpen={state.isOpen}
        onClose={actions.handleCloseModal}
        title={state.modalType === 'create' ? 'Nueva categoría' : state.modalType === 'edit' ? 'Editar categoría' : 'Eliminar categoría'}
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