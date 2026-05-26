import React from 'react';
import { useAdminItems } from '../../hooks/admin/useAdminQueries';
import { useCategories } from '../../hooks/categories/useCategoryQueries';
import { useFilters } from '../../hooks/common/useFilters';
import { useAdminItemActions } from '../../hooks/admin/useAdminActions';
import { SectionHeader } from '../../components/common/SectionHeader';
import { FeedbackState } from '../../components/common/FeedbackState';
import { Modal } from '../../components/common/Modal';
import { Pagination } from '../../components/common/Pagination';
import { AdminItemCard } from "../../components/admin/AdminItemCard.jsx";
import { ModalButtons } from "../../components/common/ModalButtons.jsx";
import { AdminItemModerateBody } from "../../components/admin/modals/AdminItemModerateBody.jsx";
import { AdminItemDeleteBody } from "../../components/admin/modals/AdminItemDeleteBody.jsx";
import { SearchFilter } from "../../components/common/SearchFilter.jsx";
import { SelectFilter } from "../../components/common/SelectFilter.jsx";
import { Target, ShieldAlert, LayoutGrid } from 'lucide-react';
import { AnimatePresence } from 'framer-motion';

const AdminItems = () => {
  const { page, setPage, searchInput, debouncedSearch, handleSearchChange, filters, handleFilterChange } =
    useFilters({ initialFilters: { status: '', category_id: '' } });

  const { data: categoriesData } = useCategories();
  const { data: itemsResponse, isLoading, isError } = useAdminItems({
    page,
    search: debouncedSearch,
    status: filters.status,
    category_id: filters.category_id,
    per_page: 9
  });

  const {
    isOpen, actionType, selectedItem, closeModal, handleOpenAction, executeAction, isPending,
    modStatus, setModStatus, modReason, setModReason
  } = useAdminItemActions();

  return (
    <div className="flex flex-col gap-6 animate-fade-in relative">
      <SectionHeader title="Gestión de" highlight="Ítems" icon={Target}>
        <div className="flex gap-2 flex-wrap">
          <SelectFilter
            icon={LayoutGrid}
            value={filters.category_id}
            onChange={(e) => handleFilterChange('category_id', e.target.value)}
            defaultOption="Todas las Categorías"
            options={categoriesData?.map(cat => ({ value: cat.id, label: cat.name })) || []}
          />
          <SelectFilter
            value={filters.status}
            onChange={(e) => handleFilterChange('status', e.target.value)}
            defaultOption="Todos los Estados"
            options={[
              { value: 'active', label: 'Activos' },
              { value: 'inactive', label: 'Inactivos' }
            ]}
          />
          <SearchFilter value={searchInput} onChange={handleSearchChange} placeholder="Buscar ítem..." />
        </div>
      </SectionHeader>

      {isLoading ? (
        <FeedbackState icon={Target} isLoading title="Cargando ítems..." />
      ) : isError ? (
        <FeedbackState icon={ShieldAlert} title="Error" description="No se pudieron cargar los ítems." iconColorClass="bg-red-100 text-red-500" />
      ) : !itemsResponse?.data?.length ? (
        <FeedbackState icon={Target} title="Sin resultados" description="No se encontraron ítems con estos filtros." />
      ) : (
        <div className="flex flex-col gap-4">
          <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            <AnimatePresence>
              {itemsResponse.data.map(item => (
                <AdminItemCard
                  key={item.id}
                  item={item}
                  onModerate={() => handleOpenAction(item, 'moderate')}
                  onDelete={() => handleOpenAction(item, 'delete')}
                />
              ))}
            </AnimatePresence>
          </div>
          <Pagination meta={itemsResponse.meta} onPageChange={setPage} />
        </div>
      )}

      <Modal
        isOpen={isOpen}
        onClose={closeModal}
        title={actionType === 'moderate' ? 'Moderar ítem' : 'Eliminar ítem'}
        footer={
          <ModalButtons
            onClose={closeModal}
            onConfirm={executeAction}
            isPending={isPending}
            confirmText="Confirmar Acción"
            actionStyle={actionType === 'delete' ? 'danger' : 'info'}
          />
        }
      >
        <div className="flex flex-col gap-4">
          {actionType === 'delete' ? (
            <AdminItemDeleteBody itemName={selectedItem?.name} />
          ) : (
            <AdminItemModerateBody
              itemName={selectedItem?.name}
              modStatus={modStatus}
              setModStatus={setModStatus}
              modReason={modReason}
              setModReason={setModReason}
            />
          )}
        </div>
      </Modal>
    </div>
  );
};

export default AdminItems;