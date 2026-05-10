import React, { useState, useEffect } from 'react';
import { useAdminItems } from '../../hooks/admin/useAdminItemQueries';
import { useAdminModerateItem, useAdminDeleteItem } from '../../hooks/admin/useAdminItemMutations';
import { SectionHeader } from '../../components/common/SectionHeader';
import { FeedbackState } from '../../components/common/FeedbackState';
import { Modal } from '../../components/common/Modal';
import { Pagination } from '../../components/common/Pagination';
import { Target, ShieldAlert, LayoutGrid } from 'lucide-react';
import { AnimatePresence } from 'framer-motion';
import { useCategories } from '../../hooks/categories/useCategoryQueries';
import { AdminItemCard } from "../../components/admin/AdminItemCard.jsx";
import { ModalButtons } from "../../components/common/ModalButtons.jsx";
import { AdminItemModerateBody } from "../../components/admin/AdminItemModerateBody.jsx";
import { AdminItemDeleteBody } from "../../components/admin/AdminItemDeleteBody.jsx";
import { SearchFilter } from "../../components/common/SearchFilter.jsx";
import { SelectFilter } from "../../components/common/SelectFilter.jsx";
import { useModal } from '../../hooks/useModal';

const AdminItems = () => {
  const [page, setPage] = useState(1);

  // 1. Separamos el input visual del valor que dispara la petición (Debounce)
  const [searchInput, setSearchInput] = useState('');
  const [debouncedSearch, setDebouncedSearch] = useState('');

  const [filterStatus, setFilterStatus] = useState('');
  const [filterCategory, setFilterCategory] = useState('');

  const { isOpen, modalType: actionType, modalData: selectedItem, openModal, closeModal } = useModal();
  const [modStatus, setModStatus] = useState('active');
  const [modReason, setModReason] = useState('');

  const { data: categoriesData } = useCategories();

  // 2. Efecto para el Debounce: Espera 400ms antes de actualizar el estado de búsqueda
  useEffect(() => {
    const timer = setTimeout(() => {
      setDebouncedSearch(searchInput);
      setPage(1); // Reseteamos la página a 1 cuando la búsqueda finaliza
    }, 400);

    return () => clearTimeout(timer); // Cleanup si el usuario sigue tecleando
  }, [searchInput]);

  // 3. El hook consume el valor debounced, no el input directo
  const { data: itemsResponse, isLoading, isError } = useAdminItems({
    page,
    search: debouncedSearch,
    status: filterStatus,
    category_id: filterCategory,
    per_page: 9
  });

  const { mutate: moderateItem, isPending: isModerating } = useAdminModerateItem();
  const { mutate: deleteItem, isPending: isDeleting } = useAdminDeleteItem();

  // 4. El handler ahora solo actualiza el input visual
  const handleSearch = (e) => {
    setSearchInput(e.target.value);
  };

  const handleOpenAction = (item, type) => {
    openModal(type, item);
    if (type === 'moderate') {
      setModStatus(item.status);
      setModReason('');
    }
  };

  const executeAction = () => {
    if (!selectedItem) return;
    if (actionType === 'moderate') {
      moderateItem({ id: selectedItem.id, status: modStatus, reason: modReason }, { onSuccess: closeModal });
    } else if (actionType === 'delete') {
      deleteItem(selectedItem.id, { onSuccess: closeModal });
    }
  };

  return (
    <div className="flex flex-col gap-6 animate-fade-in relative">
      <SectionHeader title="Gestión de" highlight="Ítems" icon={Target}>
        <div className="flex gap-2 flex-wrap">
          <SelectFilter
            icon={LayoutGrid}
            value={filterCategory}
            onChange={(e) => { setFilterCategory(e.target.value); setPage(1); }}
            defaultOption="Todas las Categorías"
            options={categoriesData?.map(cat => ({ value: cat.id, label: cat.name })) || []}
          />
          <SelectFilter
            value={filterStatus}
            onChange={(e) => { setFilterStatus(e.target.value); setPage(1); }}
            defaultOption="Todos los Estados"
            options={[
              { value: 'active', label: 'Activos' },
              { value: 'inactive', label: 'Inactivos' }
            ]}
          />
          <SearchFilter
            value={searchInput}
            onChange={handleSearch}
            placeholder="Buscar ítem..."
          />
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
        footer={<ModalButtons onClose={closeModal} onConfirm={executeAction} isPending={isModerating || isDeleting} confirmText="Confirmar Acción" actionStyle={actionType === 'delete' ? 'danger' : 'info'} />}
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