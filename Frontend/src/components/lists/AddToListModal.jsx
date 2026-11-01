import React from 'react';
import { Link } from 'react-router-dom';
import { Check, Plus } from 'lucide-react';
import Modal from '../ui/Modal.jsx';
import Button from '../ui/Button.jsx';
import InputField from '../ui/InputField.jsx';
import ListVisibilityField from './ListVisibilityField.jsx';
import { useAddToListModal } from '../../hooks/lists/useAddToListModal.js';
import { LIST_TITLE_MAX_LENGTH, LIST_VISIBILITY_LABELS } from '../../lib/lists.js';

// Modal global "Añadir a una lista" (F3-01). Se monta una sola vez en App.jsx y solo pinta algo si hay un ítem elegido.
const AddToListModal = () => {
  const { state, actions } = useAddToListModal();

  if (!state.item) return null;

  return (
    <Modal isOpen onClose={actions.handleClose} title="Añadir a una lista">
      <div className="flex flex-col gap-5">
        <p className="text-sm font-medium text-text-normal">
          Elige dónde guardar <span className="font-bold text-text-highlight">{state.item.name}</span>.
        </p>

        {state.isLoading ? (
          <p className="text-sm font-medium text-text-normal">Cargando tus listas...</p>
        ) : state.isError ? (
          <p role="alert" className="text-sm font-bold text-red-600">No se pudieron cargar tus listas.</p>
        ) : state.lists.length === 0 ? (
          <p className="text-sm font-medium text-text-normal">Todavía no tienes listas.</p>
        ) : (
          <ul className="flex max-h-60 flex-col gap-2 overflow-y-auto">
            {state.lists.map((list) => (
              <li key={list.id} className="flex items-center justify-between gap-3 rounded-2xl border border-slate-100 px-4 py-2">
                <div className="min-w-0">
                  <p className="truncate font-bold text-slate-800">{list.title}</p>
                  <p className="text-xs font-bold text-slate-400">
                    {LIST_VISIBILITY_LABELS[list.visibility]} · {list.items_count} títulos
                  </p>
                </div>
                <Button
                  onClick={() => actions.handleToggle(list)}
                  variant="ghost"
                  color={list.contains_item ? 'success' : 'primary'}
                  size="sm"
                  icon={list.contains_item ? Check : Plus}
                  disabled={state.isBusy}
                  aria-pressed={Boolean(list.contains_item)}
                >
                  {list.contains_item ? 'En la lista' : 'Añadir'}
                </Button>
              </li>
            ))}
          </ul>
        )}

        <div className="flex flex-col gap-3 border-t border-slate-100 pt-4">
          <h4 className="text-sm font-black text-text-highlight">Nueva lista</h4>
          {state.canCreate ? (
            <>
              <InputField
                label="Título"
                placeholder="Ej. Para ver en una tarde de lluvia"
                maxLength={LIST_TITLE_MAX_LENGTH}
                value={state.title}
                onChange={(e) => actions.setTitle(e.target.value)}
                disabled={state.isBusy}
              />
              <ListVisibilityField
                name="add-to-list-visibility"
                value={state.visibility}
                onChange={actions.setVisibility}
                disabled={state.isBusy}
              />
              <Button
                onClick={actions.handleCreateAndAdd}
                isLoading={state.isCreating}
                disabled={!state.title.trim() || state.isBusy}
                icon={Plus}
              >
                Crear y añadir
              </Button>
            </>
          ) : (
            <p role="alert" className="text-sm font-bold text-red-600">
              Has llegado al máximo de {state.meta.max_lists} listas.
            </p>
          )}
        </div>

        <Link
          to="/mis-listas"
          onClick={actions.handleClose}
          className="text-center text-sm font-bold text-primary hover:underline"
        >
          Gestionar mis listas
        </Link>
      </div>
    </Modal>
  );
};

export default AddToListModal;
