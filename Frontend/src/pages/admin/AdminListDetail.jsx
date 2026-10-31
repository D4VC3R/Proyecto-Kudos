import React from 'react';
import { Link } from 'react-router-dom';
import { ArrowDown, ArrowLeft, ArrowUp, ListOrdered, Plus, ShieldAlert, Trash2 } from 'lucide-react';
// Componentes
import SectionHeader from '../../components/ui/SectionHeader.jsx';
import FeedbackState from '../../components/ui/FeedbackState.jsx';
import Button from '../../components/ui/Button.jsx';
import SearchFilter from '../../components/ui/SearchFilter.jsx';
import StorageImage from '../../components/ui/StorageImage.jsx';
// Hooks
import { useAdminListDetailPage } from '../../hooks/pages/useAdminListDetailPage.js';

const AdminListDetail = () => {
  const { state, actions } = useAdminListDetailPage();

  if (state.isLoading) return <FeedbackState icon={ListOrdered} isLoading title="Cargando lista..."/>;
  if (state.isError || !state.list) {
    return <FeedbackState icon={ShieldAlert} title="Error" description="No se pudo cargar la lista." iconColorClass="bg-red-100 text-red-500"/>;
  }

  return (
    <div className="flex flex-col gap-6 animate-fade-in relative">
      <SectionHeader title={state.list.title} highlightColor="accent" icon={ListOrdered}>
        <Button onClick={actions.handleGoBack} variant="ghost" color="primary" size="sm" icon={ArrowLeft}>
          Volver a listas
        </Button>
        <Link
          to={`/listas/${state.list.slug}`}
          className="inline-flex items-center rounded-xl px-3 py-2 text-sm font-bold text-primary hover:bg-blue-50"
        >
          Ver pública
        </Link>
      </SectionHeader>

      <section aria-labelledby="admin-list-add" className="flex flex-col gap-3">
        <h3 id="admin-list-add" className="text-lg font-bold text-slate-800">Añadir ítems</h3>
        <SearchFilter
          value={state.searchInput}
          onChange={actions.handleSearchChange}
          placeholder="Buscar ítem para añadir…"
          maxWidth="max-w-md"
        />
        {state.hasSearch && (
          state.searchResults.length === 0 ? (
            !state.isSearching && <p className="text-sm font-medium text-text-normal">No hay ítems activos con ese nombre.</p>
          ) : (
            <ul className="flex flex-col gap-2">
              {state.searchResults.map((item) => (
                <li key={item.id} className="flex items-center justify-between gap-3 rounded-2xl border border-slate-100 px-4 py-2">
                  <div className="min-w-0">
                    <p className="truncate font-bold text-slate-800">{item.name}</p>
                    <p className="text-xs font-bold text-slate-400">{item.category?.name}</p>
                  </div>
                  <Button
                    onClick={() => actions.handleAdd(item)}
                    variant="ghost"
                    color="primary"
                    size="sm"
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
        <h3 id="admin-list-items" className="text-lg font-bold text-slate-800">
          Ítems de la lista ({state.entries.length})
        </h3>
        {state.entries.length === 0 ? (
          <FeedbackState icon={ListOrdered} title="Lista vacía" description="Busca un ítem arriba para añadirlo."/>
        ) : (
          <ol className="flex flex-col gap-2">
            {state.entries.map((entry, index) => {
              const thumb = entry.item.images?.[0]?.variants?.thumb;
              return (
                <li key={entry.id} className="flex items-center gap-3 rounded-2xl border border-slate-100 px-4 py-2">
                  <span className="w-6 text-center font-black text-primary">{entry.position}</span>
                  {thumb && (
                    <StorageImage
                      src={thumb}
                      alt=""
                      className="h-12 w-9 shrink-0 rounded-lg object-cover"
                    />
                  )}
                  <div className="min-w-0 flex-1">
                    <p className="truncate font-bold text-slate-800">{entry.item.name}</p>
                    {entry.item.status !== 'active' && (
                      <p className="text-xs font-bold text-red-500">Inactivo</p>
                    )}
                  </div>
                  <Button
                    onClick={() => actions.handleMove(index, -1)}
                    variant="ghost"
                    color="neutral"
                    size="iconMd"
                    icon={ArrowUp}
                    aria-label="Subir"
                    disabled={index === 0 || state.isBusy}
                  />
                  <Button
                    onClick={() => actions.handleMove(index, 1)}
                    variant="ghost"
                    color="neutral"
                    size="iconMd"
                    icon={ArrowDown}
                    aria-label="Bajar"
                    disabled={index === state.entries.length - 1 || state.isBusy}
                  />
                  <Button
                    onClick={() => actions.handleRemove(entry.item.id)}
                    variant="ghost"
                    color="danger"
                    size="iconMd"
                    icon={Trash2}
                    aria-label="Quitar de la lista"
                    disabled={state.isBusy}
                  />
                </li>
              );
            })}
          </ol>
        )}
      </section>
    </div>
  );
};

export default AdminListDetail;
