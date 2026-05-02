import React, { useState } from 'react';
import { UserCircle, EyeOff, Eye, Trash2 } from 'lucide-react';
import { formatDate } from '../../lib/formatters';
import { useSessionStore } from '../../store/useSessionStore';
import { useAdminHideComment, useAdminUnhideComment } from '../../hooks/admin/useAdminCommentMutations';
import { Modal } from '../common/Modal';
import { useDeleteComment } from '../../hooks/comments/useCommentMutations';
import { useModal } from '../../hooks/useModal';
import {CommentHideBody} from "./CommentHideBody.jsx";
import {CommentDeleteBody} from "./CommentDeleteBody.jsx";
import {ModalButtons} from "../common/ModalButtons.jsx";

export const Comment = ({ comment }) => {
  const { user } = useSessionStore();
  const isAdmin = user?.role === 'admin';
  const isOwner = user?.id === comment.user?.id;

  const { mutate: hideComment, isPending: isHiding } = useAdminHideComment();
  const { mutate: unhideComment, isPending: isUnhiding } = useAdminUnhideComment();
  const { mutate: deleteComment, isPending: isDeleting } = useDeleteComment();

  const { isOpen, modalType, openModal, closeModal } = useModal();
  const [hideReason, setHideReason] = useState('');

  const executeAction = () => {
    if (modalType === 'hide') {
      hideComment({ id: comment.id, reason: hideReason }, { onSuccess: closeModal });
    } else if (modalType === 'delete') {
      deleteComment(comment.id, { onSuccess: closeModal });
    }
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
              <div className="flex items-center gap-2">
                {isAdmin && !comment.is_hidden && (
                  <button onClick={() => openModal('hide')} className="text-orange-500 hover:text-orange-700 transition" title="Ocultar (Moderación)">
                    <EyeOff size={16} />
                  </button>
                )}
                {isAdmin && comment.is_hidden && (
                  <button onClick={() => unhideComment(comment.id)} disabled={isUnhiding} className="text-green-500 hover:text-green-700 transition" title="Restaurar">
                    <Eye size={16} />
                  </button>
                )}
                {(isAdmin || isOwner) && (
                  <button onClick={() => openModal('delete')} className="text-slate-400 hover:text-red-500 transition" title="Eliminar">
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
        isOpen={isOpen}
        onClose={closeModal}
        title={modalType === 'hide' ? 'Ocultar comentario' : 'Eliminar comentario'}
        footer={
          <ModalButtons
            onClose={closeModal}
            onConfirm={executeAction}
            isPending={isDeleting || isHiding}
            confirmText="Confirmar"
            actionStyle={modalType === 'delete' ? 'danger' : 'warning'}
          />
        }
      >
        {modalType === 'delete' ? (
          <CommentDeleteBody />
        ) : modalType === 'hide' ? (
          <CommentHideBody hideReason={hideReason} setHideReason={setHideReason} />
        ) : null}
      </Modal>
    </>
  );
};
