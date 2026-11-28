import React from 'react';
import { Link } from 'react-router-dom';
import clsx from 'clsx';
import { Edit2, Heart, ListX, ShieldAlert } from 'lucide-react';
// Componentes
import PageHeader from '../components/ui/PageHeader.jsx';
import Chip from '../components/ui/Chip.jsx';
import FeedbackState from '../components/ui/FeedbackState.jsx';
import HeaderSkeleton from '../components/ui/skeletons/HeaderSkeleton.jsx';
import SliderSkeleton from '../components/ui/skeletons/SliderSkeleton.jsx';
import ItemCard from '../components/items/ItemCard.jsx';
import { ITEM_GRID_CLASSES } from '../components/items/ItemGrid.jsx';
import Button from '../components/ui/Button.jsx';
import ReportMenu from '../components/reports/ReportMenu.jsx';
// Hooks
import { useListPage } from '../hooks/pages/useListPage.js';
import { hasText } from '../lib/text.js';
import { LIST_VISIBILITY_LABELS } from '../lib/lists.js';
import { resolveCategoryIcon } from '../lib/formatters.js';
import { FOCUS_RING } from '../components/layout/headerStyles.js';

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

  const { list, entries } = state;
  const categories = [
    ...new Map(
      entries.map((entry) => entry.item?.category).filter(Boolean).map((category) => [category.slug, category]),
    ).values(),
  ];

  return (
    <div className="flex w-full flex-col gap-8">
      <PageHeader kicker="Lista" title={list.title} subtitle={list.description}>
        {state.isOwner && (
          <Button as={Link} to={`/mis-listas/${list.id}`} variant="secondary" icon={Edit2}>
            Editar
          </Button>
        )}
        <Button
          onClick={actions.handleToggleLike}
          variant="secondary"
          isLoading={state.isLiking}
          disabled={state.likeDisabled}
          aria-pressed={state.liked}
          aria-label={state.liked ? 'Quitar me gusta' : 'Me gusta'}
        >
          <Heart size={16} className={clsx('shrink-0', state.liked && 'fill-current text-danger')} />
          Me gusta
          <span className="tabular-nums">{state.likesCount}</span>
        </Button>
        {state.canReport && (
          <div className="relative">
            <ReportMenu type="list" id={list.id} label="lista" />
          </div>
        )}
      </PageHeader>

      <p data-list-meta className="flex flex-wrap items-center gap-2 text-small text-ink-muted">
        <span>
          {list.author ? (
            <>
              Lista de{' '}
              <Link
                to={`/u/${list.author.username}`}
                className={`text-accent underline decoration-1 underline-offset-3 hover:decoration-2 ${FOCUS_RING}`}
              >
                @{list.author.username}
              </Link>
            </>
          ) : (
            list.is_editorial && 'Lista editorial'
          )}
          {(list.author || list.is_editorial) && ' · '}
          <span className="tabular-nums">{entries.length}</span> {entries.length === 1 ? 'elemento' : 'elementos'}
        </span>
        {categories.map((category) => {
          const Icon = resolveCategoryIcon(category);
          return <Icon key={category.slug} size={16} className="text-cat-icon" aria-hidden="true" />;
        })}
        {categories.length > 0 && (
          <span className="sr-only">Incluye: {categories.map((category) => category.name).join(', ')}</span>
        )}
        {state.isOwner && <Chip>{LIST_VISIBILITY_LABELS[list.visibility]}</Chip>}
      </p>

      {state.isOwner && list.is_hidden_by_moderation && (
        <p role="alert" className="flex items-center gap-2 text-small font-semibold text-danger">
          <ShieldAlert size={18} className="shrink-0" />
          Ocultada por moderación: no puede volver a ser pública.
        </p>
      )}

      {entries.length === 0 ? (
        <FeedbackState icon={ListX} title="Esta lista todavía está vacía" titleAs="h2" />
      ) : (
        <ol data-list-entries className={ITEM_GRID_CLASSES}>
          {entries.map((entry, index) => (
            <li key={entry.id} className="flex min-w-0 flex-col gap-2">
              {list.is_ranked && (
                <span className="font-display text-h3 tabular-nums text-accent">#{entry.position}</span>
              )}
              <ItemCard item={entry.item} index={index} />
              {hasText(entry.note) && <p className="text-small text-ink-muted">{entry.note}</p>}
            </li>
          ))}
        </ol>
      )}
    </div>
  );
};

export default ListPage;
