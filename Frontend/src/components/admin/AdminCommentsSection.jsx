import { AdminCommentsProvider } from '../../context/AdminCommentsProvider.jsx';
import { useAdminCommentsContext } from '../../hooks/admin/useAdminContexts';
import { StateCard } from '../common/StateCard';
import { PaginationControls } from '../common/PaginationControls';
import { AdminCommentsFilters } from './AdminCommentsFilters';
import { AdminCommentsTable } from './AdminCommentsTable';
import { AdminSectionHeader } from './shared/AdminSectionHeader';

const AdminCommentsSectionContent = () => {
  const {
    itemId,
    comments,
    isLoadingComments,
    isCommentsError,
    commentsError,
    isFetchingComments,
    currentPage,
    lastPage,
    total,
    canGoPrev,
    canGoNext,
    goToFirstPage,
    goToPreviousPage,
    goToNextPage,
    goToLastPage,
  } = useAdminCommentsContext();

  const shouldShowHint = !itemId;
  const showTable = !shouldShowHint && !isLoadingComments && !isCommentsError && comments.length > 0;
  const showEmpty = !shouldShowHint && !isLoadingComments && !isCommentsError && comments.length === 0;

  const hintContent = shouldShowHint ? <StateCard message="Introduce un UUID de item para listar comentarios moderables." /> : null;

  return (
      <section className="space-y-4 rounded-2xl border border-slate-800 bg-slate-950/30 p-4">
        <AdminSectionHeader
            subtitle="Consulta comentarios por item y aplica acciones de ocultar o restaurar."
            title="Moderacion de comentarios"
        />

        <AdminCommentsFilters />
        {hintContent}

        {/* Sustituimos los AdminDataStates por StateCard */}
        {!shouldShowHint && isLoadingComments && <StateCard message="Cargando comentarios..." />}
        {!shouldShowHint && isCommentsError && <StateCard error={commentsError} tone="error" />}
        {showEmpty && <StateCard message="No hay comentarios para el item seleccionado." />}

        {showTable && (
            <>
              <AdminCommentsTable />
              <PaginationControls
                  canGoNext={canGoNext}
                  canGoPrev={canGoPrev}
                  currentPage={currentPage}
                  entityLabel="comentarios"
                  isFetching={isFetchingComments}
                  lastPage={lastPage}
                  onFirst={goToFirstPage}
                  onLast={goToLastPage}
                  onNext={goToNextPage}
                  onPrev={goToPreviousPage}
                  total={total}
              />
            </>
        )}
      </section>
  );
};

export const AdminCommentsSection = () => {
  return (
      <AdminCommentsProvider>
        <AdminCommentsSectionContent />
      </AdminCommentsProvider>
  );
};