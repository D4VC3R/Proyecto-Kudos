import React from 'react';
import { Link } from 'react-router-dom';
import { ArrowLeft, Eye, ListPlus, ListX, ShieldAlert } from 'lucide-react';
import { Reorder } from 'framer-motion';
// Componentes
import PageHeader from '../components/ui/PageHeader.jsx';
import SectionHead from '../components/ui/SectionHead.jsx';
import Card from '../components/ui/Card.jsx';
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
    <div className="flex w-full flex-col gap-8">
      <PageHeader kicker="Editar lista" title={state.list.title}>
        <Button onClick={actions.handleGoBack} variant="ghost" icon={ArrowLeft}>
          Volver a mis listas
        </Button>
        <Button as={Link} to={`/listas/${state.list.slug}`} variant="secondary" icon={Eye}>
          Ver lista
        </Button>
      </PageHeader>

      <Card as="section" aria-labelledby="my-list-data" className="flex flex-col gap-4">
        <h2 id="my-list-data" className="font-display text-h3 text-ink">Datos de la lista</h2>
        {state.isHiddenByModeration && (
          <p role="alert" className="flex items-start gap-2 text-small font-semibold text-danger">
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
      </Card>

      <section aria-labelledby="my-list-items" className="flex flex-col gap-3">
        <SectionHead id="my-list-items" title={`Títulos de la lista (${state.entries.length})`} />
        {state.entries.length === 0 ? (
          <FeedbackState
            icon={ListPlus}
            title="Lista vacía"
            titleAs="h3"
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
