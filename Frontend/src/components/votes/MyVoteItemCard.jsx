import React from 'react';
import { Star, Trash2,  Clock,  XCircle } from 'lucide-react';
import { Modal } from '../common/Modal';
import { ModalButtons } from '../common/ModalButtons';
import { formatDate } from '../../lib/formatters';
import { useModal } from '../../hooks/common/useModal.js';
import { Button } from '../common/Button';

export const MyVoteItemCard = ({ vote, isDeleting, onDelete }) => {
  const { isOpen, openModal, closeModal } = useModal();
  const isSkip = vote.type === 'skip';

  const handleDeleteConfirm = () => {
    onDelete(vote.id);
    closeModal();
  };

  const item = vote.item;
  const imageUrl = item?.images?.[0]?.path;

  return (
    <>
      <div className="flex flex-col sm:flex-row sm:items-center justify-between gap-4 rounded-2xl bg-white p-5 shadow-sm ring-1 ring-slate-200 transition-shadow hover:shadow-md">
        <div className="flex items-start gap-4 flex-1">

          <div className={`mt-1 flex h-16 w-14 shrink-0 items-center justify-center rounded-xl overflow-hidden shadow-inner ${
            !imageUrl ? (isSkip ? 'bg-slate-100 text-slate-400' : 'bg-yellow-50 text-yellow-500') : 'bg-slate-100'
          }`}>
            {imageUrl ? (
              <img src={imageUrl} alt={item?.name || 'Ítem'} className="h-full w-full object-cover" />
            ) : (
              isSkip ? <XCircle size={24} /> : <Star size={24} fill="currentColor" />
            )}
          </div>

          <div className="flex flex-col w-full">
            <div className="flex items-center gap-3 flex-wrap">
              <h4 className="text-lg font-bold text-slate-900 line-clamp-1">{vote.item?.name || 'Ítem desconocido'}</h4>
              <span className={`inline-flex items-center gap-1.5 rounded-full px-2.5 py-0.5 text-xs font-bold border ${
                isSkip ? 'bg-slate-50 text-slate-600 border-slate-200' : 'bg-yellow-50 text-yellow-700 border-yellow-200'
              }`}>
                {isSkip ? 'Pasado' : 'Votado'}
              </span>
            </div>

            <div className="flex items-center gap-3 mt-1 text-sm font-medium text-slate-500">
              <span className="text-blue-600">{vote.item?.category?.name || 'Categoría'}</span>
              <span>•</span>
              <span className="flex items-center gap-1"><Clock size={14} /> {formatDate(vote.voted_at)}</span>
            </div>

            {!isSkip && (
              <div className="mt-3 flex items-center gap-1 bg-slate-50 rounded-xl px-3 py-2 w-fit border border-slate-100">
                <span className="text-slate-500 text-sm font-bold mr-1">Puntuación:</span>
                <span className="text-lg font-black text-slate-800">{vote.score}</span>
                <span className="text-slate-400 text-sm">/ 10</span>
                <Star size={16} className="text-yellow-400 ml-1" fill="currentColor" />
              </div>
            )}
          </div>
        </div>

        <div className="flex items-center gap-2 self-end sm:self-center">
          <Button
            title="Eliminar registro"
            onClick={() => openModal()}
            isLoading={isDeleting}
            variant="ghost"
            color="danger"
            size="iconLg"
            icon={Trash2}
          />
        </div>
      </div>

      <Modal
        isOpen={isOpen}
        onClose={closeModal}
        title="Eliminar registro"
        footer={
          <ModalButtons
            onClose={closeModal}
            onConfirm={handleDeleteConfirm}
            isPending={isDeleting}
            confirmText="Sí, Eliminar"
            actionStyle="danger"
          />
        }
      >
        <p className="text-slate-600 font-medium">
          ¿Estás seguro de que deseas eliminar este registro de voto para <strong>{vote.item?.name}</strong>?
        </p>
      </Modal>
    </>
  );
};