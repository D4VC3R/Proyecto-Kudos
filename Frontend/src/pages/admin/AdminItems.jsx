import React from 'react';
import { Target, ShieldAlert, LayoutGrid } from 'lucide-react';
import { AnimatePresence } from 'framer-motion';
// Componentes
import SectionHeader from '../../components/common/SectionHeader';
import FeedbackState from '../../components/common/FeedbackState';
import Modal from '../../components/common/Modal';
import Pagination from '../../components/common/Buttons/Pagination.jsx';
import AdminItemCard from "../../components/admin/AdminItemCard.jsx";
import ModalButtons from "../../components/common/Buttons/ModalButtons.jsx";
import AdminItemModerateBody from "../../components/admin/modals/AdminItemModerateBody.jsx";
import AdminItemDeleteBody from "../../components/admin/modals/AdminItemDeleteBody.jsx";
import SearchFilter from "../../components/common/SearchFilter.jsx";
import SelectFilter from "../../components/common/SelectFilter.jsx";
// Hooks
import { useAdminItemsPage } from '../../hooks/pages/useAdminItemsPage.js';

const AdminItems = () => {
  const { state, actions } = useAdminItemsPage();

  return (
    <div className="flex flex-col gap-6 animate-fade-in relative">
      <SectionHeader title="Gestión de" highlight="Ítems" highlightColor="accent" icon={Target}>
        <div className="flex gap-2 flex-wrap">
          <SelectFilter
            icon={LayoutGrid}
            value={state.filters.category_id}
            onChange={(e) => actions.handleFilterChange('category_id', e.target.value)}
            defaultOption="Todas las Categorías"
            options={state.categoryOptions}
          />
          <SelectFilter
            value={state.filters.status}
            onChange={(e) => actions.handleFilterChange('status', e.target.value)}
            defaultOption="Todos los Estados"
            options={[
              { value: 'active', label: 'Activos' },
              { value: 'inactive', label: 'Inactivos' }
            ]}
          />
          <SearchFilter value={state.searchInput} onChange={actions.handleSearchChange} placeholder="Buscar ítem..."/>
        </div>
      </SectionHeader>

      {state.isLoading ? (
        <FeedbackState icon={Target} isLoading title="Cargando ítems..."/>
      ) : state.isError ? (
        <FeedbackState icon={ShieldAlert} title="Error" description="No se pudieron cargar los ítems." iconColorClass="bg-red-100 text-red-500"/>
      ) : !state.itemsResponse?.data?.length ? (
        <FeedbackState icon={Target} title="Sin resultados" description="No se encontraron ítems con estos filtros."/>
      ) : (
        <div className="flex flex-col gap-4">
          <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            <AnimatePresence>
              {state.itemsResponse.data.map(item => (
                <AdminItemCard
                  key={item.id}
                  item={item}
                  onModerate={() => actions.handleOpenAction(item, 'moderate')}
                  onDelete={() => actions.handleOpenAction(item, 'delete')}
                />
              ))}
            </AnimatePresence>
          </div>
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