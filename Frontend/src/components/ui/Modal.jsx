import React, {useId} from 'react';
import {AnimatePresence} from 'framer-motion';
import {RemoveScroll} from 'react-remove-scroll';
import {X} from 'lucide-react';
import Button from './Button.jsx';
import Fade from '../animations/Fade.jsx';
import ModalTransition from '../animations/ModalTransition.jsx';
import {useDialogFocus} from '../../hooks/common/useDialogFocus.js';

const Modal = ({isOpen, onClose, title, children, footer, returnFocusRef}) => {
  const titleId = useId();
  const {dialogRef, handleKeyDown} = useDialogFocus({isOpen, onClose, returnFocusRef});

  return (
    <AnimatePresence>
      {isOpen && (
        <div className="fixed inset-0 z-50 flex items-center justify-center p-4 sm:p-0">

          <Fade
            onClick={onClose}
            className="absolute inset-0 bg-ink/40 backdrop-blur-sm dark:bg-canvas/80"
          />

          <RemoveScroll className="relative w-full max-w-lg">
            <ModalTransition
              className="flex flex-col overflow-hidden rounded-lg border border-line bg-surface shadow-raised"
            >
              <div
                ref={dialogRef}
                role="dialog"
                aria-modal="true"
                aria-labelledby={title ? titleId : undefined}
                tabIndex={-1}
                onKeyDown={handleKeyDown}
                className="flex flex-col focus:outline-none"
              >
                <div className="flex items-center justify-between border-b border-line px-6 py-4">
                  <h3 id={titleId} className="font-display text-h3 text-ink">{title}</h3>
                  <Button
                    onClick={onClose}
                    variant="ghost"
                    icon={X}
                    aria-label="Cerrar modal"
                    className="w-11 px-0"
                  />
                </div>

                <div className="p-6">
                  {children}
                </div>

                {footer && (
                  <div className="bg-canvas px-6 py-4 flex items-center justify-end gap-3 border-t border-line">
                    {footer}
                  </div>
                )}
              </div>
            </ModalTransition>
          </RemoveScroll>

        </div>
      )}
    </AnimatePresence>
  );
};

export default Modal;