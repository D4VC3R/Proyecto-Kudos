import { useAdminProposalsContext } from '../../hooks/admin';
import { AdminFilterActionButtons } from './shared/AdminFilterActionButtons';
import { AdminFilterInput } from './shared/AdminFilterInput';
import { AdminFiltersContainer } from './shared/AdminFiltersContainer';
import { AdminFilterSelect } from './shared/AdminFilterSelect';

export const AdminProposalsFilters = () => {
  const {
    searchInput,
    statusFilter,
    perPage,
    updateSearchInput,
    updateStatusFilter,
    updatePerPage,
    applyFilters,
    resetFilters,
  } = useAdminProposalsContext();

  const statusOptions = [
    { value: '', label: 'Todos los estados' },
    { value: 'pending', label: 'pending' },
    { value: 'accepted', label: 'accepted' },
    { value: 'rejected', label: 'rejected' },
    { value: 'changes_requested', label: 'changes_requested' },
  ];

  const perPageOptions = [
    { value: 10, label: '10' },
    { value: 15, label: '15' },
    { value: 30, label: '30' },
  ];

  return (
    <AdminFiltersContainer
      as="form"
      className="sm:grid-cols-[1fr_auto_auto_auto]"
      onSubmit={(event) => {
        event.preventDefault();
        applyFilters();
      }}
    >
      <AdminFilterInput
        onChange={(event) => updateSearchInput(event.target.value)}
        placeholder="Buscar propuesta por nombre o descripcion"
        type="text"
        value={searchInput}
      />

      <AdminFilterSelect onChange={(event) => updateStatusFilter(event.target.value)} options={statusOptions} value={statusFilter} />

      <AdminFilterSelect onChange={(event) => updatePerPage(event.target.value)} options={perPageOptions} value={perPage} />

      <AdminFilterActionButtons onSecondary={resetFilters} />
    </AdminFiltersContainer>
  );
};

