import React, { useState } from 'react';
import { useAdminProposals } from '../../hooks/admin/useAdminProposalQueries';
import { useReviewProposal } from '../../hooks/admin/useAdminProposalMutations';
import { SectionHeader } from '../../components/common/SectionHeader';
import { FeedbackState } from '../../components/common/FeedbackState';
import { Modal } from '../../components/common/Modal';
import { Pagination } from '../../components/common/Pagination';
import { FileText, ShieldAlert, LayoutGrid } from 'lucide-react';
import { AnimatePresence } from 'framer-motion';
import { useCategories } from '../../hooks/categories/useCategoryQueries';
import {AdminProposalCard} from "../../components/admin/AdminProposalCard.jsx";
import {ModalButtons} from "../../components/common/ModalButtons.jsx";
import {AdminProposalReviewBody} from "../../components/admin/AdminProposalReviewBody.jsx";
import {SearchFilter} from "../../components/common/SearchFilter.jsx";
import {SelectFilter} from "../../components/common/SelectFilter.jsx";
import { useModal } from '../../hooks/useModal';

const AdminProposals = () => {
  const [page, setPage] = useState(1);
  const [search, setSearch] = useState('');
  const [filterStatus, setFilterStatus] = useState('pending'); // default
  const [filterCategory, setFilterCategory] = useState('');
  const { isOpen, modalType: actionType, modalData: selectedProposal, openModal, closeModal } = useModal();
  const [adminNotes, setAdminNotes] = useState('');

  const { data: categoriesData } = useCategories();
  const { data: proposalsResponse, isLoading, isError } = useAdminProposals({
    page,
    search,
    status: filterStatus,
    category_id: filterCategory,
    per_page: 9
  });
  const { mutate: reviewProposal, isPending } = useReviewProposal();

  const handleSearch = (e) => {
    setSearch(e.target.value);
    setPage(1);
  };

  const executeAction = () => {
    if (!selectedProposal) return;
    reviewProposal({
      id: selectedProposal.id,
      status: actionType,
      admin_notes: adminNotes
    }, { onSuccess: closeModal });
  };

  return (
    <div className="flex flex-col gap-6 animate-fade-in relative">
      <SectionHeader title="Revisión de" highlight="Propuestas" icon={FileText}>
        <div className="flex gap-2 flex-wrap">
          <SelectFilter
            icon={LayoutGrid}
            value={filterCategory}
            onChange={(e) => { setFilterCategory(e.target.value); setPage(1); }}
            defaultOption="Todas las Categorías"
            options={categoriesData?.map(cat => ({ value: cat.id, label: cat.name })) || []}
          />
          <SelectFilter
            value={filterStatus}
            onChange={(e) => { setFilterStatus(e.target.value); setPage(1); }}
            defaultOption="Todas"
            options={[
              { value: 'pending', label: 'Pendientes' },
              { value: 'accepted', label: 'Aceptadas' },
              { value: 'rejected', label: 'Rechazadas' },
              { value: 'changes_requested', label: 'Cambios' }
            ]}
          />
          <SearchFilter
            value={search}
            onChange={handleSearch}
            placeholder="Buscar propuesta..."
          />
        </div>
      </SectionHeader>
      {isLoading ? (
        <FeedbackState icon={FileText} isLoading title="Cargando propuestas..." />
      ) : isError ? (
        <FeedbackState icon={ShieldAlert} title="Error" description="No se pudieron cargar." iconColorClass="bg-red-100 text-red-500" />
      ) : !proposalsResponse?.data?.length ? (
        <FeedbackState icon={FileText} title="Sin resultados" description="No hay propuestas con estos filtros." />
      ) : (
        <div className="flex flex-col gap-4">
          <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            <AnimatePresence>
              {proposalsResponse.data.map(prop => (
                <AdminProposalCard
                  key={prop.id}
                  proposal={prop}
                  onAccept={() => { openModal('accepted', prop); setAdminNotes(''); }}
                  onReject={() => { openModal('rejected', prop); setAdminNotes(''); }}
                />
              ))}
            </AnimatePresence>
          </div>
          <Pagination meta={proposalsResponse.meta} onPageChange={setPage} />
        </div>
      )}
      {/* Action Modal */}
      <Modal
        isOpen={isOpen}
        onClose={closeModal}
        title={actionType === 'accepted' ? 'Aprobar Propuesta' : 'Rechazar Propuesta'}
        footer={<ModalButtons onClose={closeModal} onConfirm={executeAction} isPending={isPending} actionStyle={actionType === 'accepted' ? 'success' : 'danger'} />}
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
