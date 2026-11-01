import React from 'react';
import Modal from '../ui/Modal.jsx';
import ModalButtons from '../ui/ModalButtons.jsx';
import TextAreaField from '../ui/TextAreaField.jsx';
import { REPORT_REASONS, REPORT_DETAILS_MAX_LENGTH } from '../../lib/reports.js';

// Modal para reportar un contenido (F3-04): motivo obligatorio y detalles opcionales ("Otro" los exige).
const ReportModal = ({ isOpen, onClose, onSubmit, isPending, reason, details, error, onReasonChange, onDetailsChange }) => (
  <Modal
    isOpen={isOpen}
    onClose={onClose}
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
        <legend className="mb-2 text-sm font-bold text-slate-700">¿Qué pasa con este contenido?</legend>
        {REPORT_REASONS.map((option) => (
          <label key={option.value} className="flex cursor-pointer items-center gap-2 text-sm text-text-highlight">
            <input
              type="radio"
              name="report-reason"
              value={option.value}
              checked={reason === option.value}
              onChange={() => onReasonChange(option.value)}
              disabled={isPending}
              className="h-4 w-4 accent-blue-600"
            />
            {option.label}
          </label>
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

      {error && <p role="alert" className="text-sm font-bold text-red-600">{error}</p>}

      <p className="text-sm text-text-normal">
        Revisaremos tu reporte. Si el contenido incumple las normas, lo ocultaremos o lo eliminaremos.
      </p>
    </div>
  </Modal>
);

export default ReportModal;
