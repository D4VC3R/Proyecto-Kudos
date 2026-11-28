import React from 'react';
import { ListPlus, Plus, ShieldAlert } from 'lucide-react';
// Componentes
import PageHeader from '../components/ui/PageHeader.jsx';
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
      <PageHeader title="Mis listas" subtitle="Agrupa títulos de cualquier categoría y decide quién puede verlos.">
        <div className="flex items-center gap-4">
          <p className="text-small text-ink-muted">
            <span className="font-semibold tabular-nums text-ink">{state.total}/{state.maxLists}</span> listas
          </p>
          <Button onClick={actions.handleOpenCreate} icon={Plus} disabled={!state.canCreate}>
            Nueva lista
          </Button>
        </div>
      </PageHeader>

      {state.isLoading ? (
        <FeedbackState icon={ListPlus} isLoading title="Cargando tus listas..." titleAs="h2" />
      ) : state.isError ? (
        <FeedbackState
          icon={ShieldAlert}
          title="Error"
          titleAs="h2"
          description="No se pudieron cargar tus listas."
          iconColorClass="bg-danger/10 text-danger"
        />
      ) : state.lists.length === 0 ? (
        <FeedbackState
          icon={ListPlus}
          title="Todavía no tienes listas"
          titleAs="h2"
          description="Crea una o añade títulos desde cualquier ficha con «Añadir a lista»."
        />
      ) : (
        <ul className="grid grid-cols-1 gap-4 md:grid-cols-2 lg:grid-cols-3">
          {state.lists.map((list) => (
            <li key={list.id} className="min-w-0">
              <MyListCard list={list} onDelete={actions.handleOpenDelete} />
            </li>
          ))}
        </ul>
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
          <p className="text-ink">
            ¿Seguro que quieres eliminar la lista{' '}
            <span className="font-semibold">"{state.selectedList?.title}"</span>?
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
