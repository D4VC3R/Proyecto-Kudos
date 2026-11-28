import React from 'react';
import { FileText, ShieldAlert, LayoutGrid, Search } from 'lucide-react';
// Componentes
import SectionHead from '../../components/ui/SectionHead.jsx';
import FeedbackState from '../../components/ui/FeedbackState.jsx';
import FilterBar from '../../components/ui/FilterBar.jsx';
import Input from '../../components/ui/Input.jsx';
import Select from '../../components/ui/Select.jsx';
import Modal from '../../components/ui/Modal.jsx';
import ModalButtons from "../../components/ui/ModalButtons.jsx";
import Pagination from '../../components/ui/Pagination.jsx';
import AdminProposalsTable from "../../components/admin/AdminProposalsTable.jsx";
import AdminProposalReviewBody from "../../components/admin/modals/AdminProposalReviewBody.jsx";
// Hooks
import { useAdminProposalsPage } from '../../hooks/pages/useAdminProposalsPage.js';

const AdminProposals = () => {
  const { state, actions } = useAdminProposalsPage();

  return (
    <div className="flex flex-col gap-6 animate-fade-in relative">
      <SectionHead title="Revisión de propuestas"/>

      <FilterBar label="Filtrar propuestas">
        <Select
          label="Categoría"
          icon={LayoutGrid}
          value={state.filters.category_id}
          onChange={(e) => actions.handleFilterChange('category_id', e.target.value)}
          placeholder="Todas"
          selectClassName="w-auto"
          options={state.categoryOptions}
        />
        <Select
          label="Estado"
          value={state.filters.status}
          onChange={(e) => actions.handleFilterChange('status', e.target.value)}
          placeholder="Todas"
          selectClassName="w-auto"
          options={[
            { value: 'pending', label: 'Pendientes' },
            { value: 'accepted', label: 'Aceptadas' },
            { value: 'rejected', label: 'Rechazadas' },
            { value: 'changes_requested', label: 'Cambios' }
          ]}
        />
        <Input
          type="search"
          label="Buscar"
          icon={Search}
          className="w-full sm:w-64"
          value={state.searchInput}
          onChange={actions.handleSearchChange}
          placeholder="Buscar propuesta..."
        />
      </FilterBar>

      {state.isLoading ? (
        <FeedbackState icon={FileText} isLoading titleAs="h3" title="Cargando propuestas..."/>
      ) : state.isError ? (
        <FeedbackState icon={ShieldAlert} titleAs="h3" title="Error" description="No se pudieron cargar." iconColorClass="bg-danger/10 text-danger"/>
      ) : !state.proposalsResponse?.data?.length ? (
        <FeedbackState icon={FileText} titleAs="h3" title="Sin resultados" description="No hay propuestas con estos filtros."/>
      ) : (
        <div className="flex flex-col gap-4">
          <AdminProposalsTable
            proposals={state.proposalsResponse.data}
            onAccept={(prop) => actions.handleOpenAction(prop, 'accepted')}
            onReject={(prop) => actions.handleOpenAction(prop, 'rejected')}
          />
          <Pagination meta={state.proposalsResponse.meta} onPageChange={actions.setPage}/>
        </div>
      )}

      <Modal
        isOpen={state.isOpen}
        onClose={actions.closeModal}
        title={state.actionType === 'accepted' ? 'Aprobar Propuesta' : 'Rechazar Propuesta'}
        footer={
          <ModalButtons
            onClose={actions.closeModal}
            onConfirm={actions.executeAction}
            isPending={state.isPending}
            actionStyle={state.actionType === 'accepted' ? 'success' : 'danger'}
          />
        }
      >
        <div className="flex flex-col gap-4">
          <AdminProposalReviewBody
            actionType={state.actionType}
            proposalName={state.selectedProposal?.name}
            adminNotes={state.adminNotes}
            setAdminNotes={actions.setAdminNotes}
          />
        </div>
      </Modal>
    </div>
  );
};

export default AdminProposals;