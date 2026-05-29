import React from 'react';
import { FileText, ShieldAlert, LayoutGrid } from 'lucide-react';
import { AnimatePresence } from 'framer-motion';
// Componentes
import SectionHeader from '../../components/ui/SectionHeader.jsx';
import FeedbackState from '../../components/ui/FeedbackState.jsx';
import Modal from '../../components/ui/Modal.jsx';
import ModalButtons from "../../components/ui/ModalButtons.jsx";
import Pagination from '../../components/ui/Pagination.jsx';
import SearchFilter from "../../components/ui/SearchFilter.jsx";
import SelectFilter from "../../components/ui/SelectFilter.jsx";
import AdminProposalCard from "../../components/admin/AdminProposalCard.jsx";
import AdminProposalReviewBody from "../../components/admin/modals/AdminProposalReviewBody.jsx";
// Hooks
import { useAdminProposalsPage } from '../../hooks/pages/useAdminProposalsPage.js';

const AdminProposals = () => {
  const { state, actions } = useAdminProposalsPage();

  return (
    <div className="flex flex-col gap-6 animate-fade-in relative">
      <SectionHeader title="Revisión de" highlight="Propuestas" icon={FileText}>
        <div className="flex gap-2 flex-wrap">
          <SelectFilter
            icon={LayoutGrid}
            value={state.filters.category_id}
            onChange={(e) => actions.handleFilterChange('category_id', e.target.value)}
            defaultOption="Todas las Categorías"
            options={state.categoryOptions}
          />
          <SelectFilter
            value={state.filters.status}
            onChange={(e) => actions.handleFilterChange('status', e.target.value)}
            defaultOption="Todas"
            options={[
              { value: 'pending', label: 'Pendientes' },
              { value: 'accepted', label: 'Aceptadas' },
              { value: 'rejected', label: 'Rechazadas' },
              { value: 'changes_requested', label: 'Cambios' }
            ]}
          />
          <SearchFilter value={state.searchInput} onChange={actions.handleSearchChange} placeholder="Buscar propuesta..."/>
        </div>
      </SectionHeader>

      {state.isLoading ? (
        <FeedbackState icon={FileText} isLoading title="Cargando propuestas..."/>
      ) : state.isError ? (
        <FeedbackState icon={ShieldAlert} title="Error" description="No se pudieron cargar." iconColorClass="bg-red-100 text-red-500"/>
      ) : !state.proposalsResponse?.data?.length ? (
        <FeedbackState icon={FileText} title="Sin resultados" description="No hay propuestas con estos filtros."/>
      ) : (
        <div className="flex flex-col gap-4">
          <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            <AnimatePresence>
              {state.proposalsResponse.data.map(prop => (
                <AdminProposalCard
                  key={prop.id}
                  proposal={prop}
                  onAccept={() => actions.handleOpenAction(prop, 'accepted')}
                  onReject={() => actions.handleOpenAction(prop, 'rejected')}
                />
              ))}
            </AnimatePresence>
          </div>
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