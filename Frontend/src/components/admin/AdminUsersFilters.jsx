import { useAdminUsersContext } from '../../hooks/admin';
import { AdminFilterActionButtons } from './shared/AdminFilterActionButtons';
import { AdminFilterInput } from './shared/AdminFilterInput';
import { AdminFiltersContainer } from './shared/AdminFiltersContainer';
import { AdminFilterSelect } from './shared/AdminFilterSelect';

export const AdminUsersFilters = () => {
  const {
    searchInput,
    banState,
    roleFilter,
    perPage,
    updateSearchInput,
    updateBanState,
    updateRoleFilter,
    updatePerPage,
    resetFilters,
  } = useAdminUsersContext();

  const roleOptions = [
    { value: '', label: 'Todos los roles' },
    { value: 'admin', label: 'admin' },
    { value: 'user', label: 'user' },
  ];

  const banStateOptions = [
    { value: '', label: 'Todos los estados' },
    { value: 'active', label: 'Activos' },
    { value: 'temporary', label: 'Baneo temporal' },
    { value: 'permanent', label: 'Baneo permanente' },
    { value: 'expired', label: 'Baneo expirado' },
  ];

  const perPageOptions = [
    { value: 10, label: '10' },
    { value: 20, label: '20' },
    { value: 50, label: '50' },
  ];

  return (
    <AdminFiltersContainer className="sm:grid-cols-2 xl:grid-cols-5">
      <AdminFilterInput
        className="xl:col-span-2"
        onChange={(event) => updateSearchInput(event.target.value)}
        placeholder="Buscar por nombre o email"
        type="text"
        value={searchInput}
      />

      <AdminFilterSelect onChange={(event) => updateRoleFilter(event.target.value)} options={roleOptions} value={roleFilter} />

      <AdminFilterSelect onChange={(event) => updateBanState(event.target.value)} options={banStateOptions} value={banState} />

      <div className="flex gap-2">
        <AdminFilterSelect onChange={(event) => updatePerPage(event.target.value)} options={perPageOptions} value={perPage} />
        <AdminFilterActionButtons onSecondary={resetFilters} primaryLabel={null} />
      </div>
    </AdminFiltersContainer>
  );
};

