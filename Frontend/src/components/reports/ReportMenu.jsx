import React from 'react';
import { Flag, MoreHorizontal } from 'lucide-react';
import clsx from 'clsx';
import Button from '../ui/Button.jsx';
import ReportModal from './ReportModal.jsx';
import { MENU_ITEM } from '../layout/headerStyles.js';
import { useReportMenu } from '../../hooks/reports/useReportMenu.js';

// Menú "⋯ → Reportar" de un contenido (F3-04). `type`: comment | list | user. El contenedor padre debe ser `relative`.
const ReportMenu = ({ type, id, label }) => {
  const { state, actions, refs } = useReportMenu({ type, id });

  return (
    <div ref={refs.containerRef} className="relative">
      <Button
        ref={refs.buttonRef}
        onClick={actions.toggleMenu}
        variant="ghost"
        icon={MoreHorizontal}
        className="min-h-9 w-9 px-0"
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
            'absolute z-20 w-44 rounded-md border border-line bg-surface p-1.5 shadow-raised',
            state.placement.alignLeft ? 'left-0' : 'right-0',
            state.placement.openUp ? 'bottom-full mb-1' : 'top-full mt-1',
          )}
        >
          <button
            type="button"
            role="menuitem"
            onClick={actions.openReport}
            className={clsx(MENU_ITEM, 'font-semibold')}
          >
            <Flag size={16} className="shrink-0" aria-hidden="true" />
            Reportar
          </button>
        </div>
      )}

      <ReportModal
        isOpen={state.isModalOpen}
        returnFocusRef={refs.buttonRef}
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
