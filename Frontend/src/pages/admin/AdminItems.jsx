import React from 'react';
import { Target, ShieldAlert, LayoutGrid, Search } from 'lucide-react';
// Componentes
import SectionHead from '../../components/ui/SectionHead.jsx';
import FeedbackState from '../../components/ui/FeedbackState.jsx';
import FilterBar from '../../components/ui/FilterBar.jsx';
import Input from '../../components/ui/Input.jsx';
import Select from '../../components/ui/Select.jsx';
import Modal from '../../components/ui/Modal.jsx';
import Pagination from '../../components/ui/Pagination.jsx';
import AdminItemsTable from "../../components/admin/AdminItemsTable.jsx";
import ModalButtons from "../../components/ui/ModalButtons.jsx";
import AdminItemModerateBody from "../../components/admin/modals/AdminItemModerateBody.jsx";
import AdminItemDeleteBody from "../../components/admin/modals/AdminItemDeleteBody.jsx";
// Hooks
import { useAdminItemsPage } from '../../hooks/pages/useAdminItemsPage.js';

const AdminItems = () => {
  const { state, actions } = useAdminItemsPage();

  return (
    <div className="flex flex-col gap-6 animate-fade-in relative">
      <SectionHead title="Gestión de ítems"/>

      <FilterBar label="Filtrar ítems">
        <Select
          label="Categoría"
          icon={LayoutGrid}
          value={state.filters.category_id}
          onChange={(e) => actions.handleFilterChange('category_id', e.target.value)}
          placeholder="Todas"
          selectClassName="w-auto"
          options={state.categoryOptions}
        />
        <Select
          label="Estado"
          value={state.filters.status}
          onChange={(e) => actions.handleFilterChange('status', e.target.value)}
          placeholder="Todos"
          selectClassName="w-auto"
          options={[
            { value: 'active', label: 'Activos' },
            { value: 'inactive', label: 'Inactivos' }
          ]}
        />
        <Input
          type="search"
          label="Buscar"
          icon={Search}
          className="w-full sm:w-64"
          value={state.searchInput}
          onChange={actions.handleSearchChange}
          placeholder="Buscar ítem..."
        />
      </FilterBar>

      {state.isLoading ? (
        <FeedbackState icon={Target} isLoading titleAs="h3" title="Cargando ítems..."/>
      ) : state.isError ? (
        <FeedbackState icon={ShieldAlert} titleAs="h3" title="Error" description="No se pudieron cargar los ítems." iconColorClass="bg-danger/10 text-danger"/>
      ) : !state.itemsResponse?.data?.length ? (
        <FeedbackState icon={Target} titleAs="h3" title="Sin resultados" description="No se encontraron ítems con estos filtros."/>
      ) : (
        <div className="flex flex-col gap-4">
          <AdminItemsTable
            items={state.itemsResponse.data}
            onModerate={(item) => actions.handleOpenAction(item, 'moderate')}
            onDelete={(item) => actions.handleOpenAction(item, 'delete')}
          />
          <Pagination meta={state.itemsResponse.meta} onPageChange={actions.setPage}/>
        </div>
      )}

      <Modal
        isOpen={state.isOpen}
        onClose={actions.closeModal}
        title={state.actionType === 'moderate' ? 'Moderar ítem' : 'Eliminar ítem'}
        footer={
          <ModalButtons
            onClose={actions.closeModal}
            onConfirm={actions.executeAction}
            isPending={state.isPending}
            confirmText="Confirmar Acción"
            actionStyle={state.actionType === 'delete' ? 'danger' : 'info'}
          />
        }
      >
        <div className="flex flex-col gap-4">
          {state.actionType === 'delete' ? (
            <AdminItemDeleteBody itemName={state.selectedItem?.name}/>
          ) : (
            <AdminItemModerateBody
              itemName={state.selectedItem?.name}
              modStatus={state.modStatus}
              setModStatus={actions.setModStatus}
              modReason={state.modReason}
              setModReason={actions.setModReason}
            />
          )}
        </div>
      </Modal>
    </div>
  );
};

export default AdminItems;