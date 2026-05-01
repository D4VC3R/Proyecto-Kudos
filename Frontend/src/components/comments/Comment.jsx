import React, { useState } from 'react';
import { UserCircle, EyeOff, Eye, Trash2 } from 'lucide-react';
import { formatDate } from '../../lib/formatters';
import { useSessionStore } from '../../store/useSessionStore';
import { useAdminHideComment, useAdminUnhideComment } from '../../hooks/admin/useAdminCommentMutations';
import { Modal } from '../common/Modal';
import { InputField } from '../common/InputField';
import { useDeleteComment } from '../../hooks/comments/useCommentMutations';
export const Comment = ({ comment }) => {
  const { user } = useSessionStore();
  const isAdmin = user?.role === 'admin';
  const isOwner = user?.id === comment.user?.id;
  const { mutate: hideComment, isPending: isHiding } = useAdminHideComment();
  const { mutate: unhideComment, isPending: isUnhiding } = useAdminUnhideComment();
  const { mutate: deleteComment, isPending: isDeleting } = useDeleteComment();
  const [modalOpen, setModalOpen] = useState(false);
  const [modalAction, setModalAction] = useState(false); // 'hide' or 'delete'
  const [hideReason, setHideReason] = useState('');
  const openHideModal = () => {
    setModalAction('hide');
    setModalOpen(true);
  };
  const openDeleteModal = () => {
    setModalAction('delete');
    setModalOpen(true);
  };
  const handleAction = () => {
    if (modalAction === 'hide') {
      hideComment({ id: comment.id, reason: hideReason }, { onSuccess: () => setModalOpen(false) });
    } else if (modalAction === 'delete') {
      deleteComment(comment.id, { onSuccess: () => setModalOpen(false) });
    }
  };
  const handleUnhide = () => {
    unhideComment(comment.id);
  };
  return (
    <>
      <div className={`p-4 rounded-xl border flex gap-3 ${comment.is_hidden ? 'bg-orange-50 border-orange-100 opacity-80' : 'bg-slate-50 border-slate-100'}`}>
        <div className="shrink-0 flex items-center justify-center w-10 h-10 rounded-full bg-slate-200 text-slate-500 overflow-hidden">
          {comment.user?.avatar ? (
            <img src={comment.user.avatar} alt={comment.user.name} className="w-full h-full object-cover" />
          ) : (
            <UserCircle size={24} />
          )}
        </div>
        <div className="flex-1">
          <div className="flex justify-between items-start mb-1">
            <span className="font-bold text-sm text-slate-800">{comment.user?.name || 'Usuario'}</span>
            <div className="flex items-center gap-3">
              <span className="text-xs text-slate-400">{formatDate(comment.created_at)}</span>
              {/* Admin & Owner Actions */}
              <div className="flex items-center gap-2">
                {isAdmin && !comment.is_hidden && (
                  <button onClick={openHideModal} className="text-orange-500 hover:text-orange-700 transition" title="Ocultar (Moderación)">
                    <EyeOff size={16} />
                  </button>
                )}
                {isAdmin && comment.is_hidden && (
                  <button onClick={handleUnhide} disabled={isUnhiding} className="text-green-500 hover:text-green-700 transition" title="Restaurar">
                    <Eye size={16} />
                  </button>
                )}
                {(isAdmin || isOwner) && (
                  <button onClick={openDeleteModal} className="text-slate-400 hover:text-red-500 transition" title="Eliminar">
                    <Trash2 size={16} />
                  </button>
                )}
              </div>
            </div>
          </div>
          {comment.is_hidden ? (
            <div className="text-sm mt-1 text-orange-700 italic font-medium">
              [Comentario oculto por moderación: {comment.hidden_reason || 'Sin motivo especificado'}]
            </div>
          ) : (
            <p className="text-sm text-slate-700 mt-1">{comment.content}</p>
          )}
        </div>
      </div>
      <Modal
        isOpen={modalOpen}
        onClose={() => setModalOpen(false)}
        title={modalAction === 'hide' ? 'Ocultar comentario' : 'Eliminar comentario'}
        footer={
          <>
            <button onClick={() => setModalOpen(false)} className="px-4 py-2 rounded-xl text-slate-600 hover:bg-slate-100 font-bold transition-colors">
              Cancelar
            </button>
            <button 
              onClick={handleAction} 
              disabled={isDeleting || isHiding}
              className={`px-4 py-2 rounded-xl font-bold text-white transition-colors shadow-sm ${modalAction === 'delete' ? 'bg-red-500 hover:bg-red-600' : 'bg-orange-500 hover:bg-orange-600'}`}
            >
              Confirmar
            </button>
          </>
        }
      >
        <div className="flex flex-col gap-4">
          {modalAction === 'delete' ? (
             <p className="text-slate-600">¿Estás seguro de que deseas eliminar este comentario permanentemente?</p>
          ) : (
             <>
               <p className="text-slate-600">El comentario se ocultará públicamente pero se mantendrá en el sistema. Los admins podrán restaurarlo.</p>
               <InputField 
                 label="Razón (obligatoria)"
                 placeholder="Motivo para ocultará..."
                 value={hideReason}
                 onChange={(e) => setHideReason(e.target.value)}
               />
             </>
          )}
        </div>
      </Modal>
    </>
  );
};
