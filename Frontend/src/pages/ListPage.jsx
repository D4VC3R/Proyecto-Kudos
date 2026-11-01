import React from 'react';
import { Link } from 'react-router-dom';
import { Edit2, Heart, ListX, ShieldAlert } from 'lucide-react';
// Componentes
import SectionHeader from '../components/ui/SectionHeader.jsx';
import FeedbackState from '../components/ui/FeedbackState.jsx';
import HeaderSkeleton from '../components/ui/skeletons/HeaderSkeleton.jsx';
import SliderSkeleton from '../components/ui/skeletons/SliderSkeleton.jsx';
import ItemCard from '../components/items/ItemCard.jsx';
import Button from '../components/ui/Button.jsx';
import ReportMenu from '../components/reports/ReportMenu.jsx';
// Hooks
import { useListPage } from '../hooks/pages/useListPage.js';
import { hasText } from '../lib/text.js';
import { LIST_VISIBILITY_LABELS } from '../lib/lists.js';

const ListPage = () => {
  const { state, actions } = useListPage();

  if (state.isLoading) {
    return (
      <div className="flex w-full flex-col gap-8">
        <HeaderSkeleton />
        <SliderSkeleton />
      </div>
    );
  }

  if (state.isError || !state.list) {
    return (
      <FeedbackState
        icon={ListX}
        title="No encontramos esta lista"
        description="Puede que sea privada o que se haya borrado."
        actionText="Volver al inicio"
        onAction={actions.handleGoHome}
      />
    );
  }

  return (
    <div className="flex w-full flex-col gap-6">
      <SectionHeader size="large" title={state.list.title} subtitle={state.list.description}>
        {state.isOwner && (
          <>
            <span className="inline-flex items-center rounded-full bg-background px-3 py-1 text-sm font-bold text-slate-500">
              {LIST_VISIBILITY_LABELS[state.list.visibility]}
            </span>
            <Link
              to={`/mis-listas/${state.list.id}`}
              className="inline-flex items-center gap-2 rounded-xl px-3 py-2 text-sm font-bold text-primary hover:bg-blue-50"
            >
              <Edit2 size={16} className="shrink-0" />
              Editar
            </Link>
          </>
        )}
        <Button
          onClick={actions.handleToggleLike}
          variant="ghost"
          color={state.liked ? 'danger' : 'neutral'}
          radius="full"
          size="sm"
          isLoading={state.isLiking}
          disabled={state.likeDisabled}
          aria-pressed={state.liked}
          aria-label={state.liked ? 'Quitar me gusta' : 'Me gusta'}
        >
          <Heart size={16} className={state.liked ? 'shrink-0 fill-current' : 'shrink-0'} />
          Me gusta
          <span>{state.likesCount}</span>
        </Button>
        {state.canReport && (
          <div className="relative">
            <ReportMenu type="list" id={state.list.id} label="lista" />
          </div>
        )}
      </SectionHeader>

      {state.isOwner && state.list.is_hidden_by_moderation && (
        <p role="alert" className="flex items-center gap-2 text-sm font-bold text-red-600">
          <ShieldAlert size={18} className="shrink-0" />
          Ocultada por moderación: no puede volver a ser pública.
        </p>
      )}

      {state.entries.length === 0 ? (
        <FeedbackState icon={ListX} title="Esta lista todavía está vacía" />
      ) : (
        <>
          <p className="text-sm font-bold text-text-normal">{state.entries.length} títulos</p>
          <ol className="grid grid-cols-2 gap-4 sm:grid-cols-3 lg:grid-cols-4 xl:grid-cols-6">
            {state.entries.map((entry) => (
              <li key={entry.id} className="flex flex-col gap-2">
                {state.list.is_ranked && (
                  <span className="text-sm font-black text-primary">#{entry.position}</span>
                )}
                <ItemCard item={entry.item} onClick={() => actions.handleItemClick(entry.item)} />
                {hasText(entry.note) && <p className="text-sm text-text-normal">{entry.note}</p>}
              </li>
            ))}
          </ol>
        </>
      )}
    </div>
  );
};

export default ListPage;
