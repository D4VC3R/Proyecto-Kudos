import React from 'react';
import { Flag, MoreHorizontal } from 'lucide-react';
import clsx from 'clsx';
import Button from '../ui/Button.jsx';
import ReportModal from './ReportModal.jsx';
import { useReportMenu } from '../../hooks/reports/useReportMenu.js';

// Menú "⋯ → Reportar" de un contenido (F3-04). `type`: comment | list | user. El contenedor padre debe ser `relative`.
const ReportMenu = ({ type, id, label }) => {
  const { state, actions, refs } = useReportMenu({ type, id });

  return (
    <div ref={refs.containerRef} className="relative">
      <Button
        onClick={actions.toggleMenu}
        variant="ghost"
        color="neutral"
        size="iconSm"
        icon={MoreHorizontal}
        aria-label={`Más opciones del ${label}`}
        aria-haspopup="menu"
        aria-expanded={state.isMenuOpen}
        aria-controls={state.isMenuOpen ? state.menuId : undefined}
      />

      {state.isMenuOpen && (
        <div
          id={state.menuId}
          ref={refs.panelRef}
          role="menu"
          className={clsx(
            'absolute z-20 w-40 overflow-hidden rounded-xl bg-surface shadow-xl ring-1 ring-border',
            state.placement.alignLeft ? 'left-0' : 'right-0',
            state.placement.openUp ? 'bottom-full mb-1' : 'top-full mt-1',
          )}
        >
          <button
            type="button"
            role="menuitem"
            onClick={actions.openReport}
            className="flex w-full items-center gap-2 px-4 py-2.5 text-left text-sm font-bold text-text-highlight transition-colors hover:bg-background focus:bg-background focus:outline-none"
          >
            <Flag size={16} className="shrink-0" />
            Reportar
          </button>
        </div>
      )}

      <ReportModal
        isOpen={state.isModalOpen}
        onClose={actions.closeModal}
        onSubmit={actions.submit}
        isPending={state.isPending}
        reason={state.reason}
        details={state.details}
        error={state.error}
        onReasonChange={actions.changeReason}
        onDetailsChange={actions.changeDetails}
      />
    </div>
  );
};

export default ReportMenu;
