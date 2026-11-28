import React from 'react';
import Modal from '../ui/Modal.jsx';
import ModalButtons from '../ui/ModalButtons.jsx';
import Radio from '../ui/Radio.jsx';
import TextAreaField from '../ui/TextAreaField.jsx';
import { REPORT_REASONS, REPORT_DETAILS_MAX_LENGTH } from '../../lib/reports.js';

// Modal para reportar un contenido (F3-04): motivo obligatorio y detalles opcionales ("Otro" los exige).
const ReportModal = ({ isOpen, onClose, onSubmit, isPending, reason, details, error, onReasonChange, onDetailsChange, returnFocusRef }) => (
  <Modal
    isOpen={isOpen}
    onClose={onClose}
    returnFocusRef={returnFocusRef}
    title="Reportar contenido"
    footer={
      <ModalButtons
        onClose={onClose}
        onConfirm={onSubmit}
        isPending={isPending}
        confirmText="Enviar reporte"
        actionStyle="danger"
      />
    }
  >
    <div className="flex flex-col gap-4">
      <fieldset className="flex flex-col gap-2">
        <legend className="mb-2 text-small font-semibold text-ink">¿Qué pasa con este contenido?</legend>
        {REPORT_REASONS.map((option) => (
          <Radio
            key={option.value}
            name="report-reason"
            value={option.value}
            checked={reason === option.value}
            onChange={() => onReasonChange(option.value)}
            disabled={isPending}
            label={option.label}
          />
        ))}
      </fieldset>

      <TextAreaField
        label={reason === 'otro' ? 'Detalles (obligatorio)' : 'Detalles'}
        placeholder="Cuéntanos qué ha pasado..."
        rows={3}
        maxLength={REPORT_DETAILS_MAX_LENGTH}
        value={details}
        onChange={(e) => onDetailsChange(e.target.value)}
        disabled={isPending}
      />

      {error && <p role="alert" className="text-small font-semibold text-danger">{error}</p>}

      <p className="text-small text-ink-muted">
        Revisaremos tu reporte. Si el contenido incumple las normas, lo ocultaremos o lo eliminaremos.
      </p>
    </div>
  </Modal>
);

export default ReportModal;
