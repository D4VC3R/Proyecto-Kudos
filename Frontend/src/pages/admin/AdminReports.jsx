import React from 'react';
import { Flag, ShieldAlert } from 'lucide-react';
import { AnimatePresence } from 'framer-motion';
// Componentes
import SectionHeader from '../../components/ui/SectionHeader.jsx';
import FeedbackState from '../../components/ui/FeedbackState.jsx';
import Modal from '../../components/ui/Modal.jsx';
import ModalButtons from "../../components/ui/ModalButtons.jsx";
import Pagination from '../../components/ui/Pagination.jsx';
import SelectFilter from "../../components/ui/SelectFilter.jsx";
import AdminReportCard from "../../components/admin/AdminReportCard.jsx";
import AdminReportResolveBody from "../../components/admin/modals/AdminReportResolveBody.jsx";
// Hooks y utilidades
import { useAdminReportsPage } from '../../hooks/pages/useAdminReportsPage.js';
import { REPORT_STATUS_OPTIONS, REPORTABLE_TYPE_OPTIONS } from '../../lib/reports.js';

const MODAL_TITLES = {
  hide: 'Ocultar contenido',
  delete: 'Eliminar contenido',
  dismiss: 'Descartar reporte',
};
const MODAL_STYLES = {
  hide: 'warning',
  delete: 'danger',
  dismiss: 'primary',
};

const AdminReports = () => {
  const { state, actions } = useAdminReportsPage();

  return (
    <div className="flex flex-col gap-6 animate-fade-in relative">
      <SectionHeader title="Cola de" highlight="Reportes" highlightColor="kudos" icon={Flag}>
        <div className="flex gap-2 flex-wrap">
          <SelectFilter
            label="Estado"
            value={state.filters.status}
            onChange={(e) => actions.handleFilterChange('status', e.target.value)}
            defaultOption="Todos"
            options={REPORT_STATUS_OPTIONS}
          />
          <SelectFilter
            label="Tipo de contenido"
            icon={Flag}
            value={state.filters.reportable_type}
            onChange={(e) => actions.handleFilterChange('reportable_type', e.target.value)}
            defaultOption="Todo el contenido"
            options={REPORTABLE_TYPE_OPTIONS}
          />
        </div>
      </SectionHeader>

      {state.isLoading ? (
        <FeedbackState icon={Flag} isLoading title="Cargando reportes..."/>
      ) : state.isError ? (
        <FeedbackState icon={ShieldAlert} title="Error" description="No se pudieron cargar." iconColorClass="bg-red-100 text-red-500"/>
      ) : !state.reportsResponse?.data?.length ? (
        <FeedbackState icon={Flag} title="Sin resultados" description="No hay reportes con estos filtros."/>
      ) : (
        <div className="flex flex-col gap-4">
          <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            <AnimatePresence>
              {state.reportsResponse.data.map(report => (
                <AdminReportCard
                  key={report.id}
                  report={report}
                  onHide={(r) => actions.handleOpenAction(r, 'hide')}
                  onDelete={(r) => actions.handleOpenAction(r, 'delete')}
                  onDismiss={(r) => actions.handleOpenAction(r, 'dismiss')}
                />
              ))}
            </AnimatePresence>
          </div>
          <Pagination meta={state.reportsResponse.meta} onPageChange={actions.setPage}/>
        </div>
      )}

      <Modal
        isOpen={state.isOpen}
        onClose={actions.closeModal}
        title={MODAL_TITLES[state.actionType] ?? 'Resolver reporte'}
        footer={
          <ModalButtons
            onClose={actions.closeModal}
            onConfirm={actions.executeAction}
            isPending={state.isPending}
            actionStyle={MODAL_STYLES[state.actionType] ?? 'primary'}
          />
        }
      >
        <div className="flex flex-col gap-4">
          <AdminReportResolveBody
            actionType={state.actionType}
            report={state.selectedReport}
            note={state.note}
            setNote={actions.setNote}
          />
        </div>
      </Modal>
    </div>
  );
};

export default AdminReports;
