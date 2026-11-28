import React from 'react';
import { Link } from 'react-router-dom';
import { ArrowLeft, ListOrdered, Plus, Search, ShieldAlert } from 'lucide-react';
// Componentes
import SectionHead from '../../components/ui/SectionHead.jsx';
import FeedbackState from '../../components/ui/FeedbackState.jsx';
import Button from '../../components/ui/Button.jsx';
import Input from '../../components/ui/Input.jsx';
import AdminListEntriesTable from '../../components/admin/AdminListEntriesTable.jsx';
// Hooks
import { useAdminListDetailPage } from '../../hooks/pages/useAdminListDetailPage.js';

const AdminListDetail = () => {
  const { state, actions } = useAdminListDetailPage();

  if (state.isLoading) return <FeedbackState icon={ListOrdered} isLoading titleAs="h2" title="Cargando lista..."/>;
  if (state.isError || !state.list) {
    return <FeedbackState icon={ShieldAlert} titleAs="h2" title="Error" description="No se pudo cargar la lista." iconColorClass="bg-danger/10 text-danger"/>;
  }

  return (
    <div className="flex flex-col gap-6 animate-fade-in relative">
      <SectionHead title={state.list.title}>
        <Button onClick={actions.handleGoBack} variant="ghost" icon={ArrowLeft}>
          Volver a listas
        </Button>
        <Button as={Link} to={`/listas/${state.list.slug}`} variant="secondary">
          Ver pública
        </Button>
      </SectionHead>

      <section aria-labelledby="admin-list-add" className="flex flex-col gap-3">
        <h3 id="admin-list-add" className="font-display text-h3 text-ink">Añadir ítems</h3>
        <Input
          type="search"
          label="Buscar ítem para añadir"
          icon={Search}
          className="max-w-md"
          value={state.searchInput}
          onChange={actions.handleSearchChange}
          placeholder="Buscar ítem para añadir…"
        />
        {state.hasSearch && (
          state.searchResults.length === 0 ? (
            !state.isSearching && <p className="text-small text-ink-muted">No hay ítems activos con ese nombre.</p>
          ) : (
            <ul className="flex flex-col gap-2">
              {state.searchResults.map((item) => (
                <li key={item.id} className="flex items-center justify-between gap-3 rounded-md border border-line bg-surface px-4 py-2">
                  <div className="min-w-0">
                    <p className="truncate font-semibold text-ink">{item.name}</p>
                    <p className="text-caption text-ink-muted">{item.category?.name}</p>
                  </div>
                  <Button
                    onClick={() => actions.handleAdd(item)}
                    variant="secondary"
                    icon={Plus}
                    disabled={actions.isInList(item.id) || state.isBusy}
                  >
                    Añadir
                  </Button>
                </li>
              ))}
            </ul>
          )
        )}
      </section>

      <section aria-labelledby="admin-list-items" className="flex flex-col gap-3">
        <h3 id="admin-list-items" className="font-display text-h3 text-ink">
          Ítems de la lista ({state.entries.length})
        </h3>
        {state.entries.length === 0 ? (
          <FeedbackState icon={ListOrdered} titleAs="h4" title="Lista vacía" description="Busca un ítem arriba para añadirlo."/>
        ) : (
          <AdminListEntriesTable
            entries={state.entries}
            isBusy={state.isBusy}
            onMove={actions.handleMove}
            onRemove={actions.handleRemove}
          />
        )}
      </section>
    </div>
  );
};

export default AdminListDetail;
