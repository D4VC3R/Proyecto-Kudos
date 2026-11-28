import React from 'react';
import { Link } from 'react-router-dom';
import { Check, Plus } from 'lucide-react';
import Modal from '../ui/Modal.jsx';
import Button from '../ui/Button.jsx';
import Input from '../ui/Input.jsx';
import ListVisibilityField from './ListVisibilityField.jsx';
import { useAddToListModal } from '../../hooks/lists/useAddToListModal.js';
import { LIST_TITLE_MAX_LENGTH, LIST_VISIBILITY_LABELS } from '../../lib/lists.js';

// Modal global "Añadir a una lista" (F3-01). Se monta una sola vez en App.jsx y solo pinta algo si hay un ítem elegido.
const AddToListModal = () => {
  const { state, actions } = useAddToListModal();

  if (!state.item) return null;

  // Con `aria-disabled` y no `disabled`, el botón que se acaba de pulsar conserva el foco mientras se guarda: si
  // lo perdiera, Escape y Tab dejarían de llegar al diálogo.
  const canSubmit = Boolean(state.title.trim()) && !state.isBusy;

  return (
    <Modal isOpen onClose={actions.handleClose} title="Añadir a una lista">
      <div className="flex flex-col gap-5">
        <p className="text-small text-ink-muted">
          Elige dónde guardar <span className="font-semibold text-ink">{state.item.name}</span>.
        </p>

        {state.isLoading ? (
          <p role="status" className="text-small text-ink-muted">Cargando tus listas...</p>
        ) : state.isError ? (
          <p role="alert" className="text-small font-semibold text-danger">No se pudieron cargar tus listas.</p>
        ) : state.lists.length === 0 ? (
          <p className="text-small text-ink-muted">Todavía no tienes listas.</p>
        ) : (
          <ul className="flex max-h-60 flex-col gap-2 overflow-y-auto">
            {state.lists.map((list) => (
              <li key={list.id} className="flex items-center justify-between gap-3 rounded-md border border-line px-4 py-2">
                <div className="min-w-0">
                  <p id={`add-to-list-${list.id}`} className="truncate font-semibold text-ink">{list.title}</p>
                  <p className="text-caption text-ink-muted">
                    {LIST_VISIBILITY_LABELS[list.visibility]} · {list.items_count} {list.items_count === 1 ? 'elemento' : 'elementos'}
                  </p>
                </div>
                <Button
                  onClick={() => !state.isBusy && actions.handleToggle(list)}
                  variant="secondary"
                  icon={list.contains_item ? Check : Plus}
                  aria-disabled={state.isBusy || undefined}
                  aria-pressed={Boolean(list.contains_item)}
                  aria-describedby={`add-to-list-${list.id}`}
                  className="shrink-0"
                >
                  {list.contains_item ? 'En la lista' : 'Añadir'}
                </Button>
              </li>
            ))}
          </ul>
        )}

        <div className="flex flex-col gap-3 border-t border-line pt-4">
          <h4 className="text-small font-semibold text-ink">Nueva lista</h4>
          {state.canCreate ? (
            <>
              <Input
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
                onClick={() => canSubmit && actions.handleCreateAndAdd()}
                aria-disabled={!canSubmit || undefined}
                icon={Plus}
              >
                Crear y añadir
              </Button>
            </>
          ) : (
            <p role="alert" className="text-small font-semibold text-danger">
              Has llegado al máximo de {state.meta.max_lists} listas.
            </p>
          )}
        </div>

        <Button as={Link} to="/mis-listas" onClick={actions.handleClose} variant="link" className="self-center">
          Gestionar mis listas
        </Button>
      </div>
    </Modal>
  );
};

export default AddToListModal;
