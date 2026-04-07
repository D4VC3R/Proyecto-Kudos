import { useAdminCommentsContext } from '../../hooks/admin/useAdminContexts';
import { AdminFilterActionButtons } from './shared/AdminFilterActionButtons';
import { AdminFilterInput } from './shared/AdminFilterInput';
import { AdminFiltersContainer } from './shared/AdminFiltersContainer';
import { AdminFilterSelect } from './shared/AdminFilterSelect';

const perPageOptions = [
  { value: 10, label: '10' },
  { value: 15, label: '15' },
  { value: 30, label: '30' },
];

export const AdminCommentsFilters = () => {
  const { itemIdInput, perPage, updateItemIdInput, applyItemFilter, clearItemFilter, updatePerPage } =
    useAdminCommentsContext();

  return (
    <AdminFiltersContainer
      as="form"
      className="sm:grid-cols-[1fr_auto_auto_auto]"
      onSubmit={(event) => {
        event.preventDefault();
        applyItemFilter();
      }}
    >
      <AdminFilterInput
        onChange={(event) => updateItemIdInput(event.target.value)}
        placeholder="UUID del item"
        type="text"
        value={itemIdInput}
      />

      <AdminFilterSelect onChange={(event) => updatePerPage(event.target.value)} options={perPageOptions} value={perPage} />

      <AdminFilterActionButtons onSecondary={clearItemFilter} primaryLabel="Cargar" />
    </AdminFiltersContainer>
  );
};
