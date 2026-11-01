import React from 'react';
import { ListX } from 'lucide-react';
// Componentes
import SectionHeader from '../components/ui/SectionHeader.jsx';
import FeedbackState from '../components/ui/FeedbackState.jsx';
import HeaderSkeleton from '../components/ui/skeletons/HeaderSkeleton.jsx';
import SliderSkeleton from '../components/ui/skeletons/SliderSkeleton.jsx';
import ItemCard from '../components/items/ItemCard.jsx';
import ReportMenu from '../components/reports/ReportMenu.jsx';
// Hooks
import { useListPage } from '../hooks/pages/useListPage.js';
import { hasText } from '../lib/text.js';

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
        {state.canReport && (
          <div className="relative">
            <ReportMenu type="list" id={state.list.id} label="lista" />
          </div>
        )}
      </SectionHeader>

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
