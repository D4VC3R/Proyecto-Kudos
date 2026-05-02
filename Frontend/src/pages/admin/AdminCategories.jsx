import React, { useState } from 'react';
import { useCategories } from '../../hooks/categories/useCategoryQueries';
import { useCreateCategory, useUpdateCategory, useDeleteCategory } from '../../hooks/admin/useAdminCategoryMutations';
import { SectionHeader } from '../../components/common/SectionHeader';
import { FeedbackState } from '../../components/common/FeedbackState';
import { Modal } from '../../components/common/Modal';
import { LayoutGrid, Plus, ShieldAlert, } from 'lucide-react';
import { AnimatePresence } from 'framer-motion';
import {AdminCategoryCard} from "../../components/admin/AdminCategoryCard.jsx";
import {ModalButtons} from "../../components/common/ModalButtons.jsx";
import {AdminCategoryFormBody} from "../../components/admin/AdminCategoryFormBody.jsx";
import {AdminCategoryDeleteBody} from "../../components/admin/AdminCategoryDeleteBody.jsx";
import { useModal } from '../../hooks/useModal';

const AdminCategories = () => {
  const { data: categories, isLoading, isError } = useCategories();
  const { mutate: createCat, isPending: isCreating } = useCreateCategory();
  const { mutate: updateCat, isPending: isUpdating } = useUpdateCategory();
  const { mutate: deleteCat, isPending: isDeleting } = useDeleteCategory();
  
  const { isOpen, modalType, modalData: selectedCat, openModal, closeModal } = useModal();
  const [formData, setFormData] = useState({ name: '', description: '' });

  const handleOpenAction = (cat, type) => {
    openModal(type, cat);
    if (type === 'edit') setFormData({ name: cat.name, description: cat.description });
    if (type === 'create') setFormData({ name: '', description: '' });
  };
  const handleCloseModal = () => {
    closeModal();
    setFormData({ name: '', description: '' });
  };
  const executeAction = () => {
    if (modalType === 'create') {
      createCat(formData, { onSuccess: handleCloseModal });
    } else if (modalType === 'edit') {
      updateCat({ slug: selectedCat.slug, data: formData }, { onSuccess: handleCloseModal });
    } else if (modalType === 'delete') {
      deleteCat(selectedCat.slug, { onSuccess: handleCloseModal });
    }
  };
  return (
    <div className="flex flex-col gap-6 animate-fade-in relative">
      <SectionHeader title="Gestión de" highlight="Categorías" icon={LayoutGrid}>
        <button
          onClick={() => handleOpenAction(null, 'create')}
          className="flex items-center gap-2 bg-blue-600 hover:bg-blue-700 text-white px-5 py-2.5 rounded-2xl font-bold transition-all shadow-sm shadow-blue-500/30 ring-2 ring-transparent hover:ring-blue-600 hover:ring-offset-2 hover:ring-offset-slate-50"
        >
          <Plus size={18} /> Nueva
        </button>
      </SectionHeader>
      {isLoading ? (
        <FeedbackState icon={LayoutGrid} isLoading title="Cargando categorías..." />
      ) : isError ? (
        <FeedbackState icon={ShieldAlert} title="Error" description="No se pudieron cargar las categorías." iconColorClass="bg-red-100 text-red-500" />
      ) : !categories?.length ? (
        <FeedbackState icon={LayoutGrid} title="Sin categorías" description="No hay categorías aún. Asegúrate de añadir alguna." />
      ) : (
        <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
          <AnimatePresence>
            {categories.map(cat => (
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
      {/* Action Modal */}
      <Modal
        isOpen={isOpen}
        onClose={handleCloseModal}
        title={modalType === 'create' ? 'Nueva Categoría' : modalType === 'edit' ? 'Editar Categoría' : 'Eliminar Categoría'}
        footer={<ModalButtons onClose={handleCloseModal} onConfirm={executeAction} isPending={isCreating || isUpdating || isDeleting} actionStyle={modalType === 'delete' ? 'danger' : 'primary'} />}
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
