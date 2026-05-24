import React, { useMemo } from 'react';
import { useCategories } from '../../hooks/categories/useCategoryQueries';
import { SectionHeader } from '../../components/common/SectionHeader';
import { FeedbackState } from '../../components/common/FeedbackState';
import { Modal } from '../../components/common/Modal';
import { SearchFilter } from '../../components/common/SearchFilter';
import { LayoutGrid, Plus, ShieldAlert } from 'lucide-react';
import { AnimatePresence } from 'framer-motion';
import { AdminCategoryCard } from "../../components/admin/AdminCategoryCard.jsx";
import { ModalButtons } from "../../components/common/ModalButtons.jsx";
import { AdminCategoryFormBody } from "../../components/admin/AdminCategoryFormBody.jsx";
import { AdminCategoryDeleteBody } from "../../components/admin/AdminCategoryDeleteBody.jsx";
import { Button } from '../../components/common/Button';
import { useFilters } from '../../hooks/common/useFilters';
import { useAdminCategoryActions } from '../../hooks/admin/useAdminCategoryActions';

const AdminCategories = () => {
  const { searchInput, debouncedSearch, handleSearchChange } = useFilters();
  const { data: categories, isLoading, isError } = useCategories();

  const filteredCategories = useMemo(() => {
    if (!categories) return [];
    if (!debouncedSearch) return categories;

    const lowerSearch = debouncedSearch.toLowerCase();
    return categories.filter(cat =>
      cat.name.toLowerCase().includes(lowerSearch) ||
      (cat.description && cat.description.toLowerCase().includes(lowerSearch))
    );
  }, [categories, debouncedSearch]);

  const {
    isOpen, modalType, selectedCat, handleCloseModal,
    handleOpenAction, executeAction, isPending,
    formData, setFormData
  } = useAdminCategoryActions();

  return (
    <div className="flex flex-col gap-6 animate-fade-in relative">
      <SectionHeader title="Gestión de" highlight="Categorías" icon={LayoutGrid}>
        <div className="flex gap-4 items-center flex-wrap">
          <SearchFilter
            value={searchInput}
            onChange={handleSearchChange}
            placeholder="Buscar categoría..."
          />
          <Button
            onClick={() => handleOpenAction(null, 'create')}
            variant="ring"
            color="primary"
            icon={Plus}
          >
            Nueva
          </Button>
        </div>
      </SectionHeader>

      {isLoading ? (
        <FeedbackState icon={LayoutGrid} isLoading title="Cargando categorías..." />
      ) : isError ? (
        <FeedbackState icon={ShieldAlert} title="Error" description="No se pudieron cargar las categorías." iconColorClass="bg-red-100 text-red-500" />
      ) : !filteredCategories?.length ? (
        <FeedbackState icon={LayoutGrid} title="Sin resultados" description="No se encontraron categorías." />
      ) : (
        <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
          <AnimatePresence>
            {filteredCategories.map(cat => (
              <AdminCategoryCard
                key={cat.id}
                category={cat}
                onEdit={() => handleOpenAction(cat, 'edit')}
                onDelete={() => handleOpenAction(cat, 'delete')}
              />
            ))}
          </AnimatePresence>
        </div>
      )}

      <Modal
        isOpen={isOpen}
        onClose={handleCloseModal}
        title={modalType === 'create' ? 'Nueva Categoría' : modalType === 'edit' ? 'Editar Categoría' : 'Eliminar Categoría'}
        footer={
          <ModalButtons
            onClose={handleCloseModal}
            onConfirm={executeAction}
            isPending={isPending}
            actionStyle={modalType === 'delete' ? 'danger' : 'primary'}
          />
        }
      >
        <div className="flex flex-col gap-4">
          {modalType === 'delete' ? (
            <AdminCategoryDeleteBody categoryName={selectedCat?.name} />
          ) : (
            <AdminCategoryFormBody formData={formData} setFormData={setFormData} />
          )}
        </div>
      </Modal>
    </div>
  );
};

export default AdminCategories;