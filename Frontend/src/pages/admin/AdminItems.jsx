import React, { useState } from 'react';
import { useAdminItems } from '../../hooks/admin/useAdminItemQueries';
import { useAdminModerateItem, useAdminDeleteItem } from '../../hooks/admin/useAdminItemMutations';
import { SectionHeader } from '../../components/common/SectionHeader';
import { FeedbackState } from '../../components/common/FeedbackState';
import { Modal } from '../../components/common/Modal';
import { Pagination } from '../../components/common/Pagination';
import { Target, ShieldAlert, LayoutGrid } from 'lucide-react';
import { AnimatePresence } from 'framer-motion';
import { useCategories } from '../../hooks/categories/useCategoryQueries';
import {AdminItemCard} from "../../components/admin/AdminItemCard.jsx";
import {ModalButtons} from "../../components/common/ModalButtons.jsx";
import {AdminItemModerateBody} from "../../components/admin/AdminItemModerateBody.jsx";
import {AdminItemDeleteBody} from "../../components/admin/AdminItemDeleteBody.jsx";
import {SearchFilter} from "../../components/common/SearchFilter.jsx";
import {SelectFilter} from "../../components/common/SelectFilter.jsx";


const AdminItems = () => {
  const [page, setPage] = useState(1);
  const [search, setSearch] = useState('');
  const [filterStatus, setFilterStatus] = useState('');
  const [filterCategory, setFilterCategory] = useState('');
  const [selectedItem, setSelectedItem] = useState(null);
  const [actionType, setActionType] = useState(null); // 'moderate', 'delete'
  const [modStatus, setModStatus] = useState('active');
  const [modReason, setModReason] = useState('');

  const { data: categoriesData } = useCategories();
  const { data: itemsResponse, isLoading, isError } = useAdminItems({
    page,
    search,
    status: filterStatus,
    category_id: filterCategory,
    per_page: 9
  });

  const { mutate: moderateItem, isPending: isModerating } = useAdminModerateItem();
  const { mutate: deleteItem, isPending: isDeleting } = useAdminDeleteItem();
  const handleSearch = (e) => {
    setSearch(e.target.value);
    setPage(1);
  };
  const handleOpenAction = (item, type) => {
    setSelectedItem(item);
    setActionType(type);
    if (type === 'moderate') {
      setModStatus(item.status);
      setModReason('');
    }
  };
  const handleCloseModal = () => {
    setSelectedItem(null);
    setActionType(null);
  };
  const executeAction = () => {
    if (!selectedItem) return;
    if (actionType === 'moderate') {
      moderateItem({ id: selectedItem.id, status: modStatus, reason: modReason }, { onSuccess: handleCloseModal });
    } else if (actionType === 'delete') {
      deleteItem(selectedItem.id, { onSuccess: handleCloseModal });
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
            value={search}
            onChange={handleSearch}
            placeholder="Buscar ítem..."
          />
        </div>
      </SectionHeader>
      {isLoading ? (
        <FeedbackState icon={Target} isLoading title="Cargando tems..." />
      ) : isError ? (
        <FeedbackState icon={ShieldAlert} title="Error" description="No se pudieron cargar los tems." iconColorClass="bg-red-100 text-red-500" />
      ) : !itemsResponse?.data?.length ? (
        <FeedbackState icon={Target} title="Sin resultados" description="No se encontraron tems con estáos filtros." />
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
      {/* Action Modal */}
      <Modal
        isOpen={!!actionType}
        onClose={handleCloseModal}
        title={actionType === 'moderate' ? 'Moderar item' : 'Eliminar item'}
        footer={<ModalButtons onClose={handleCloseModal} onConfirm={executeAction} isPending={isModerating || isDeleting} confirmText="Confirmar Acción" actionStyle={actionType === 'delete' ? 'danger' : 'info'} />}
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
