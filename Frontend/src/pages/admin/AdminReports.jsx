import React from 'react';
import { Flag, ShieldAlert } from 'lucide-react';
// Componentes
import SectionHead from '../../components/ui/SectionHead.jsx';
import FeedbackState from '../../components/ui/FeedbackState.jsx';
import FilterBar from '../../components/ui/FilterBar.jsx';
import Select from '../../components/ui/Select.jsx';
import Modal from '../../components/ui/Modal.jsx';
import ModalButtons from "../../components/ui/ModalButtons.jsx";
import Pagination from '../../components/ui/Pagination.jsx';
import AdminReportsTable from "../../components/admin/AdminReportsTable.jsx";
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
      <SectionHead title="Cola de reportes"/>

      <FilterBar label="Filtrar reportes">
        <Select
          label="Estado"
          value={state.filters.status}
          onChange={(e) => actions.handleFilterChange('status', e.target.value)}
          placeholder="Todos"
          selectClassName="w-auto"
          options={REPORT_STATUS_OPTIONS}
        />
        <Select
          label="Tipo de contenido"
          icon={Flag}
          value={state.filters.reportable_type}
          onChange={(e) => actions.handleFilterChange('reportable_type', e.target.value)}
          placeholder="Todo el contenido"
          selectClassName="w-auto"
          options={REPORTABLE_TYPE_OPTIONS}
        />
      </FilterBar>

      {state.isLoading ? (
        <FeedbackState icon={Flag} isLoading titleAs="h3" title="Cargando reportes..."/>
      ) : state.isError ? (
        <FeedbackState icon={ShieldAlert} titleAs="h3" title="Error" description="No se pudieron cargar." iconColorClass="bg-danger/10 text-danger"/>
      ) : !state.reportsResponse?.data?.length ? (
        <FeedbackState icon={Flag} titleAs="h3" title="Sin resultados" description="No hay reportes con estos filtros."/>
      ) : (
        <div className="flex flex-col gap-4">
          <AdminReportsTable
            reports={state.reportsResponse.data}
            onHide={(r) => actions.handleOpenAction(r, 'hide')}
            onDelete={(r) => actions.handleOpenAction(r, 'delete')}
            onDismiss={(r) => actions.handleOpenAction(r, 'dismiss')}
          />
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
