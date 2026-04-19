import React, { useState } from 'react';
import { Star, Trash2, ShieldQuestion, Clock, CheckCircle2, XCircle } from 'lucide-react';
import { Modal } from '../common/Modal';
import { formatDate } from '../../lib/formatters';

export const MyVoteItemCard = ({ vote, isDeleting, isUpdating, onDelete, onUpdate }) => {
  const [isDeleteModalOpen, setIsDeleteModalOpen] = useState(false);
  const isSkip = vote.type === 'skip';

  const handleDeleteConfirm = () => {
    onDelete(vote.id);
    setIsDeleteModalOpen(false);
  };

  const item = vote.item;

  const getVoteColor = () => {
    if (vote.type === 'skip') return 'bg-slate-100 text-slate-500 border-slate-200';
    if (vote.score >= 8) return 'bg-green-100 text-green-700 border-green-200';
    if (vote.score >= 5) return 'bg-blue-100 text-blue-700 border-blue-200';
    return 'bg-red-100 text-red-700 border-red-200';
  };

  return (
    <>
      <div className="flex flex-col sm:flex-row sm:items-center justify-between gap-4 rounded-2xl bg-white p-5 shadow-sm ring-1 ring-slate-200 transition-shadow hover:shadow-md">
        <div className="flex items-start gap-4 flex-1">
          {/* Icono de Estado (Estrella para voto, X para skip) */}
          <div className={`mt-1 flex h-12 w-12 shrink-0 items-center justify-center rounded-xl ${
            isSkip ? 'bg-slate-100 text-slate-400' : 'bg-yellow-50 text-yellow-500'
          }`}>
            {isSkip ? <XCircle size={24} /> : <Star size={24} fill="currentColor" />}
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

            {/* Si es voto, mostrar puntuacin */}
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
          <button
            title="Eliminar registro"
            onClick={() => setIsDeleteModalOpen(true)}
            disabled={isDeleting}
            className="flex items-center justify-center h-10 w-10 rounded-xl bg-slate-50 text-slate-500 hover:bg-red-50 hover:text-red-600 transition-colors disabled:opacity-50"
          >
            <Trash2 size={18} />
          </button>
        </div>
      </div>

      <Modal
        isOpen={isDeleteModalOpen}
        onClose={() => setIsDeleteModalOpen(false)}
        title="Eliminar registro"
        footer={
          <>
            <button
              onClick={() => setIsDeleteModalOpen(false)}
              className="rounded-xl px-5 py-2.5 text-sm font-bold text-slate-600 hover:bg-slate-200 transition-colors"
            >
              Cancelar
            </button>
            <button
              onClick={handleDeleteConfirm}
              className="rounded-xl bg-red-600 px-5 py-2.5 text-sm font-bold text-white hover:bg-red-700 transition-colors shadow-md border border-red-700"
            >
              Sí, Eliminar
            </button>
          </>
        }
      >
        <p className="text-slate-600 font-medium">
          ¿Estás seguro de que deseas eliminar este registro de voto para <strong>{vote.item?.name}</strong>? Podrás volver a votarlo o pasarlo si te aparece nuevamente en el carrusel.
        </p>
      </Modal>
    </>
  );
};
