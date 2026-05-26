import React, { useState } from 'react';
import { UserCircle, EyeOff, Eye, Trash2, Edit2, Check, X } from 'lucide-react';
import { formatDate } from '../../lib/formatters';
import { useSessionStore } from '../../store/useSessionStore';
import { useAdminHideComment, useAdminUnhideComment } from '../../hooks/admin/useAdminMutations';
import { Modal } from '../common/Modal';
import { useDeleteComment, useUpdateComment } from '../../hooks/items/useItemMutations';
import { useModal } from '../../hooks/common/useModal.js';
import { CommentHideBody } from "./CommentHideBody.jsx";
import { CommentDeleteBody } from "./CommentDeleteBody.jsx";
import { ModalButtons } from "../common/ModalButtons.jsx";
import { Button } from '../common/Button';
import StorageImage from '../common/StorageImage.jsx';

export const Comment = ({ comment }) => {
  const { user } = useSessionStore();
  const isAdmin = user?.role === 'admin';
  const isOwner = user?.id === comment.user?.id;
  const isEdited = comment.updated_at && comment.updated_at !== comment.created_at;

  const { mutate: hideComment, isPending: isHiding } = useAdminHideComment();
  const { mutate: unhideComment, isPending: isUnhiding } = useAdminUnhideComment();
  const { mutate: deleteComment, isPending: isDeleting } = useDeleteComment();
  const { mutate: updateComment, isPending: isUpdating } = useUpdateComment();

  const { isOpen, modalType, openModal, closeModal } = useModal();
  const [hideReason, setHideReason] = useState('');
  const [isEditing, setIsEditing] = useState(false);
  const [editContent, setEditContent] = useState(comment.content);

  const executeAction = () => {
    if (modalType === 'hide') {
      hideComment({ id: comment.id, reason: hideReason }, { onSuccess: closeModal });
    } else if (modalType === 'delete') {
      deleteComment(comment.id, { onSuccess: closeModal });
    }
  };

  const handleUpdate = () => {
    if (editContent.trim() && editContent !== comment.content) {
      updateComment({ id: comment.id, content: editContent }, { onSuccess: () => setIsEditing(false) });
    } else {
      setIsEditing(false);
    }
  };

  const author = comment.user;

  return (
    <>
      <div className={`p-4 rounded-xl border flex gap-3 ${comment.is_hidden ? 'bg-slate-100 border-slate-200 opacity-60' : 'bg-slate-50 border-slate-100'}`}>
        <div className="shrink-0 flex items-center justify-center w-10 h-10 rounded-full bg-slate-200 text-slate-500 overflow-hidden">
          <StorageImage
            src={author?.avatar || author?.profile?.avatar}
            alt={author?.name || 'Usuario'}
            className="w-full h-full"
            fallbackIcon={UserCircle}
          />
        </div>

        <div className="flex-1">
          <div className="flex justify-between items-start mb-1">
            <div className="flex items-center gap-2">
              <span className="font-bold text-sm text-slate-800">{author?.name || 'Usuario'}</span>
              {isEdited && <span className="text-[10px] text-slate-400 italic">(editado)</span>}
            </div>
            <div className="flex items-center gap-3">
              <span className="text-xs text-slate-400">{formatDate(comment.created_at)}</span>
              <div className="flex items-center gap-2">
                {isOwner && !comment.is_hidden && (
                  <Button onClick={() => { setIsEditing(true); setEditContent(comment.content); }} variant="ghost" color="primary" size="iconSm" title="Editar" icon={Edit2} />
                )}
                {isAdmin && !comment.is_hidden && (
                  <Button onClick={() => openModal('hide')} variant="ghost" color="warning" size="iconSm" title="Ocultar (Moderación)" icon={EyeOff} />
                )}
                {isAdmin && comment.is_hidden && (
                  <Button onClick={() => unhideComment(comment.id)} disabled={isUnhiding} variant="ghost" color="success" size="iconSm" title="Restaurar" icon={Eye} />
                )}
                {isAdmin && (
                  <Button onClick={() => openModal('delete')} variant="ghost" color="danger" size="iconSm" title="Eliminar" icon={Trash2} />
                )}
              </div>
            </div>
          </div>
          {comment.is_hidden && (!isAdmin && !isOwner) ? (
            <div className="text-sm mt-1 text-orange-700 italic">
              [Comentario oculto por moderación: {comment.hidden_reason || 'Sin motivo especificado'}]
            </div>
          ) : isEditing ? (
            <div className="mt-2">
              <textarea
                value={editContent}
                onChange={(e) => setEditContent(e.target.value)}
                className="w-full text-sm p-2 border rounded-lg focus:outline-none focus:ring-1 focus:ring-blue-500"
                rows={3}
                disabled={isUpdating}
              />
              <div className="flex gap-2 justify-end mt-2">
                <Button onClick={() => setIsEditing(false)} disabled={isUpdating} variant="ghost" color="neutral" size="iconSm" icon={X} />
                <Button onClick={handleUpdate} disabled={isUpdating || !editContent.trim()} variant="ghost" color="primary" size="iconSm" icon={Check} />
              </div>
            </div>
          ) : (
            <>
              <p className="text-sm text-slate-700 mt-1">{comment.content}</p>
              {comment.is_hidden && (
                <div className="text-xs mt-2 text-orange-600 italic">
                  [Oculto: {comment.hidden_reason || 'Sin motivo'}]
                </div>
              )}
            </>
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