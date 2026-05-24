import React from 'react';
import {useAdminProposals} from '../../hooks/admin/useAdminProposalQueries';
import {SectionHeader} from '../../components/common/SectionHeader';
import {FeedbackState} from '../../components/common/FeedbackState';
import {Modal} from '../../components/common/Modal';
import {Pagination} from '../../components/common/Pagination';
import {FileText, ShieldAlert, LayoutGrid} from 'lucide-react';
import {AnimatePresence} from 'framer-motion';
import {useCategories} from '../../hooks/categories/useCategoryQueries';
import {AdminProposalCard} from "../../components/admin/AdminProposalCard.jsx";
import {ModalButtons} from "../../components/common/ModalButtons.jsx";
import {AdminProposalReviewBody} from "../../components/admin/AdminProposalReviewBody.jsx";
import {SearchFilter} from "../../components/common/SearchFilter.jsx";
import {SelectFilter} from "../../components/common/SelectFilter.jsx";
import {useFilters} from '../../hooks/common/useFilters';
import {useAdminProposalActions} from '../../hooks/admin/useAdminProposalActions';

const AdminProposals = () => {
  const {
    page, setPage, searchInput, debouncedSearch, handleSearchChange, filters, handleFilterChange
  } = useFilters({initialFilters: {status: 'pending', category_id: ''}});

  const {data: categoriesData} = useCategories();
  const {data: proposalsResponse, isLoading, isError} = useAdminProposals({
    page,
    search: debouncedSearch,
    status: filters.status,
    category_id: filters.category_id,
    per_page: 9
  });

  const {
    isOpen, actionType, selectedProposal, closeModal, handleOpenAction,
    executeAction, isPending, adminNotes, setAdminNotes
  } = useAdminProposalActions();

  return (
    <div className="flex flex-col gap-6 animate-fade-in relative">
      <SectionHeader title="Revisión de" highlight="Propuestas" icon={FileText}>
        <div className="flex gap-2 flex-wrap">
          <SelectFilter
            icon={LayoutGrid}
            value={filters.category_id}
            onChange={(e) => handleFilterChange('category_id', e.target.value)}
            defaultOption="Todas las Categorías"
            options={categoriesData?.map(cat => ({value: cat.id, label: cat.name})) || []}
          />
          <SelectFilter
            value={filters.status}
            onChange={(e) => handleFilterChange('status', e.target.value)}
            defaultOption="Todas"
            options={[
              {value: 'pending', label: 'Pendientes'},
              {value: 'accepted', label: 'Aceptadas'},
              {value: 'rejected', label: 'Rechazadas'},
              {value: 'changes_requested', label: 'Cambios'}
            ]}
          />
          <SearchFilter value={searchInput} onChange={handleSearchChange} placeholder="Buscar propuesta..."/>
        </div>
      </SectionHeader>

      {isLoading ? (
        <FeedbackState icon={FileText} isLoading title="Cargando propuestas..."/>
      ) : isError ? (
        <FeedbackState icon={ShieldAlert} title="Error" description="No se pudieron cargar."
                       iconColorClass="bg-red-100 text-red-500"/>
      ) : !proposalsResponse?.data?.length ? (
        <FeedbackState icon={FileText} title="Sin resultados" description="No hay propuestas con estos filtros."/>
      ) : (
        <div className="flex flex-col gap-4">
          <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            <AnimatePresence>
              {proposalsResponse.data.map(prop => (
                <AdminProposalCard
                  key={prop.id}
                  proposal={prop}
                  onAccept={() => handleOpenAction(prop, 'accepted')}
                  onReject={() => handleOpenAction(prop, 'rejected')}
                />
              ))}
            </AnimatePresence>
          </div>
          <Pagination meta={proposalsResponse.meta} onPageChange={setPage}/>
        </div>
      )}

      <Modal
        isOpen={isOpen}
        onClose={closeModal}
        title={actionType === 'accepted' ? 'Aprobar Propuesta' : 'Rechazar Propuesta'}
        footer={<ModalButtons onClose={closeModal} onConfirm={executeAction} isPending={isPending}
                              actionStyle={actionType === 'accepted' ? 'success' : 'danger'}/>}
      >
        <div className="flex flex-col gap-4">
          <AdminProposalReviewBody
            actionType={actionType}
            proposalName={selectedProposal?.name}
            adminNotes={adminNotes}
            setAdminNotes={setAdminNotes}
          />
        </div>
      </Modal>
    </div>
  );
};

export default AdminProposals;