import React from 'react';
import { Link } from 'react-router-dom';
import { ArrowLeft, ListPlus, ListX, ShieldAlert } from 'lucide-react';
import { Reorder } from 'framer-motion';
// Componentes
import SectionHeader from '../components/ui/SectionHeader.jsx';
import FeedbackState from '../components/ui/FeedbackState.jsx';
import Button from '../components/ui/Button.jsx';
import MyListFormFields from '../components/lists/MyListFormFields.jsx';
import MyListEntryRow from '../components/lists/MyListEntryRow.jsx';
// Hooks
import { useMyListEditPage } from '../hooks/pages/useMyListEditPage.js';

const MyListEditPage = () => {
  const { state, actions } = useMyListEditPage();

  if (state.isLoading) return <FeedbackState icon={ListPlus} isLoading title="Cargando lista..." />;
  if (state.isError || !state.list) {
    return (
      <FeedbackState
        icon={ListX}
        title="No encontramos esta lista"
        description="Puede que se haya borrado o que no sea tuya."
        actionText="Volver a mis listas"
        onAction={actions.handleGoBack}
      />
    );
  }

  return (
    <div className="flex w-full flex-col gap-6">
      <SectionHeader title={state.list.title}>
        <Button onClick={actions.handleGoBack} variant="ghost" color="primary" size="sm" icon={ArrowLeft}>
          Volver a mis listas
        </Button>
        <Button as={Link} to={`/listas/${state.list.slug}`} variant="ghost">
          Ver lista
        </Button>
      </SectionHeader>

      <section aria-labelledby="my-list-data" className="flex flex-col gap-4 rounded-3xl border border-slate-100 bg-surface p-6">
        <h3 id="my-list-data" className="text-lg font-bold text-slate-800">Datos de la lista</h3>
        {state.isHiddenByModeration && (
          <p role="alert" className="flex items-start gap-2 text-sm font-bold text-red-600">
            <ShieldAlert size={18} className="mt-0.5 shrink-0" />
            Esta lista se ocultó por moderación y no puede volver a ser pública.
          </p>
        )}
        <MyListFormFields
          idPrefix="edit-list"
          formData={state.formData}
          setFormData={actions.setFormData}
          disabled={state.isSaving}
          visibilityDisabled={state.isHiddenByModeration}
        />
        <div>
          <Button onClick={actions.handleSaveData} isLoading={state.isSaving} disabled={!state.formData.title.trim()}>
            Guardar cambios
          </Button>
        </div>
      </section>

      <section aria-labelledby="my-list-items" className="flex flex-col gap-3">
        <h3 id="my-list-items" className="text-lg font-bold text-slate-800">
          Títulos de la lista ({state.entries.length})
        </h3>
        {state.entries.length === 0 ? (
          <FeedbackState
            icon={ListPlus}
            title="Lista vacía"
            description="Añade títulos desde cualquier ficha con «Añadir a lista»."
          />
        ) : (
          <Reorder.Group
            as="ol"
            axis="y"
            values={state.entries.map((entry) => entry.item.id)}
            onReorder={actions.handleReorderPreview}
            className="flex flex-col gap-2"
          >
            {state.entries.map((entry, index) => (
              <MyListEntryRow
                key={entry.item.id}
                entry={entry}
                index={index}
                total={state.entries.length}
                isRanked={state.list.is_ranked}
                isBusy={state.isBusy}
                onMove={actions.handleMove}
                onRemove={actions.handleRemove}
                onSaveNote={actions.handleSaveNote}
                onDragEnd={actions.handleDragEnd}
              />
            ))}
          </Reorder.Group>
        )}
      </section>
    </div>
  );
};

export default MyListEditPage;
