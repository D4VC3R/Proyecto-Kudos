import { useAdminItemsContext } from '../../hooks/admin';
import { AdminFilterActionButtons } from './shared/AdminFilterActionButtons';
import { AdminFilterInput } from './shared/AdminFilterInput';
import { AdminFiltersContainer } from './shared/AdminFiltersContainer';
import { AdminFilterSelect } from './shared/AdminFilterSelect';


export const AdminItemsFilters = () => {
  const {
    searchInput,
    statusFilter,
    perPage,
    updateSearchInput,
    updateStatusFilter,
    updatePerPage,
    applyFilters,
    resetFilters,
  } = useAdminItemsContext();

  const statusOptions = [
    { value: '', label: 'Todos los estados' },
    { value: 'active', label: 'Activos' },
    { value: 'inactive', label: 'Inactivos' },
  ];

  const perPageOptions = [
    { value: 10, label: '10' },
    { value: 20, label: '20' },
    { value: 50, label: '50' },
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
        placeholder="Buscar item por nombre"
        type="text"
        value={searchInput}
      />

      <AdminFilterSelect onChange={(event) => updateStatusFilter(event.target.value)} options={statusOptions} value={statusFilter} />

      <AdminFilterSelect onChange={(event) => updatePerPage(event.target.value)} options={perPageOptions} value={perPage} />

      <AdminFilterActionButtons onSecondary={resetFilters} />
    </AdminFiltersContainer>
  );
};

