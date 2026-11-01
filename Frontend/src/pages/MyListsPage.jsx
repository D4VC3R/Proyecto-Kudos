import React from 'react';
import { ListPlus, Plus, ShieldAlert } from 'lucide-react';
import { AnimatePresence } from 'framer-motion';
// Componentes
import SectionHeader from '../components/ui/SectionHeader.jsx';
import FeedbackState from '../components/ui/FeedbackState.jsx';
import Button from '../components/ui/Button.jsx';
import Modal from '../components/ui/Modal.jsx';
import ModalButtons from '../components/ui/ModalButtons.jsx';
import MyListCard from '../components/lists/MyListCard.jsx';
import MyListFormFields from '../components/lists/MyListFormFields.jsx';
// Hooks
import { useMyListsPage } from '../hooks/pages/useMyListsPage.js';

const MyListsPage = () => {
  const { state, actions } = useMyListsPage();
  const isDelete = state.modalType === 'delete';

  return (
    <div className="flex w-full flex-col gap-6">
      <SectionHeader
        size="large"
        title="Mis"
        highlight="listas"
        subtitle="Agrupa títulos de cualquier categoría y decide quién puede verlos."
      >
        <div className="flex flex-wrap items-center gap-4">
          <div className="flex min-w-[70px] flex-col items-center justify-center rounded-xl bg-background p-2">
            <span className="text-xl font-black text-text-highlight">{state.total}/{state.maxLists}</span>
            <span className="text-xs font-bold text-slate-400">listas</span>
          </div>
          <Button onClick={actions.handleOpenCreate} variant="ring" color="primary" icon={Plus} disabled={!state.canCreate}>
            Nueva lista
          </Button>
        </div>
      </SectionHeader>

      {state.isLoading ? (
        <FeedbackState icon={ListPlus} isLoading title="Cargando tus listas..." />
      ) : state.isError ? (
        <FeedbackState icon={ShieldAlert} title="Error" description="No se pudieron cargar tus listas." iconColorClass="bg-red-100 text-red-500" />
      ) : state.lists.length === 0 ? (
        <FeedbackState
          icon={ListPlus}
          title="Todavía no tienes listas"
          description="Crea una o añade títulos desde cualquier ficha con «Añadir a lista»."
        />
      ) : (
        <div className="grid grid-cols-1 gap-6 md:grid-cols-2 lg:grid-cols-3">
          <AnimatePresence>
            {state.lists.map((list) => (
              <MyListCard key={list.id} list={list} onDelete={actions.handleOpenDelete} />
            ))}
          </AnimatePresence>
        </div>
      )}

      <Modal
        isOpen={state.isOpen}
        onClose={actions.handleCloseModal}
        title={isDelete ? 'Eliminar lista' : 'Nueva lista'}
        footer={
          <ModalButtons
            onClose={actions.handleCloseModal}
            onConfirm={actions.executeAction}
            isPending={state.isPending}
            confirmText={isDelete ? 'Eliminar' : 'Crear lista'}
            actionStyle={isDelete ? 'danger' : 'primary'}
          />
        }
      >
        {isDelete ? (
          <p className="text-nav-item">
            ¿Seguro que quieres eliminar la lista{' '}
            <span className="font-bold text-text-highlight">"{state.selectedList?.title}"</span>?
            Dejará de verse y no se podrá recuperar.
          </p>
        ) : (
          <MyListFormFields
            idPrefix="create-list"
            formData={state.formData}
            setFormData={actions.setFormData}
            disabled={state.isPending}
          />
        )}
      </Modal>
    </div>
  );
};

export default MyListsPage;
