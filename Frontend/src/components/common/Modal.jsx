import React from 'react';
import { AnimatePresence } from 'framer-motion';
import { RemoveScroll } from 'react-remove-scroll';
import { X } from 'lucide-react';
import { Button } from './Button';
import { Fade } from '../animations/Fade';
import { ModalTransition } from '../animations/ModalTransition';

export const Modal = ({isOpen, onClose, title, children, footer}) => {

  return (
    <AnimatePresence>
      {isOpen && (
        <div className="fixed inset-0 z-50 flex items-center justify-center p-4 sm:p-0">

          <Fade
            onClick={onClose}
            className="absolute inset-0 bg-slate-900/40 backdrop-blur-sm"
          />

          <RemoveScroll className="relative w-full max-w-lg">
            <ModalTransition
              className="flex flex-col rounded-3xl bg-surface shadow-2xl ring-1 ring-slate-200 overflow-hidden"
            >
              <div className="flex items-center justify-between border-b border-slate-100 px-6 py-4">
                <h3 className="text-xl font-bold text-text-highlight">{title}</h3>
                <Button
                  onClick={onClose}
                  variant="ghost"
                  color="neutral"
                  size="iconMd"
                  icon={X}
                  aria-label="Cerrar modal"
                />
              </div>

              <div className="p-6">
                {children}
              </div>

              {footer && (
                <div className="bg-background px-6 py-4 flex items-center justify-end gap-3 border-t border-slate-100">
                  {footer}
                </div>
              )}
            </ModalTransition>
          </RemoveScroll>

        </div>
      )}
    </AnimatePresence>
  );
};