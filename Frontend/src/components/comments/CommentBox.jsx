import React, { useState } from 'react';
import { Loader2, MessageSquare, Send } from 'lucide-react';
// Componentes
import Modal  from '../ui/Modal.jsx';
import CommentHideBody from "./CommentHideBody.jsx";
import CommentDeleteBody from "./CommentDeleteBody.jsx";
import ModalButtons from "../ui/ModalButtons.jsx";
import Comment from './Comment';
import Button from '../ui/Button.jsx';
// Hooks
import { useForm } from 'react-hook-form';
import { useItemComments } from '../../hooks/items/useItemQueries';
import { useCreateComment, useDeleteComment } from '../../hooks/items/useItemMutations';
import { useAdminHideComment } from '../../hooks/admin/useAdminMutations';
import { useModal } from '../../hooks/common/useModal.js';

const CommentBox = ({ itemId, className = "" }) => {
  const { data: comments, isLoading } = useItemComments(itemId);
  const createCommentMutation = useCreateComment();
  const { mutate: hideComment, isPending: isHiding } = useAdminHideComment();
  const { mutate: deleteComment, isPending: isDeleting } = useDeleteComment();

  const { register, handleSubmit, reset } = useForm();

  const { isOpen, modalType, openModal, closeModal } = useModal();
  const [activeCommentId, setActiveCommentId] = useState(null);
  const [hideReason, setHideReason] = useState('');

  const onSubmit = (data) => {
    if (!data.content.trim()) return;
    createCommentMutation.mutate(
      { itemId, content: data.content },
      { onSuccess: () => reset() }
    );
  };

  const requestHide = (commentId) => {
    setActiveCommentId(commentId);
    setHideReason('');
    openModal('hide');
  };

  const requestDelete = (commentId) => {
    setActiveCommentId(commentId);
    openModal('delete');
  };

  const executeAction = () => {
    if (!activeCommentId) return;

    if (modalType === 'hide') {
      hideComment({ id: activeCommentId, reason: hideReason }, { onSuccess: closeModal });
    } else if (modalType === 'delete') {
      deleteComment(activeCommentId, { onSuccess: closeModal });
    }
  };

  if (isLoading) {
    return <div className="flex justify-center p-4"><Loader2 className="animate-spin text-blue-500" /></div>;
  }

  return (
    <div className={`flex flex-col w-full text-left min-h-0 ${className}`}>
      <h3 className="shrink-0 text-lg font-bold text-text-highlight mb-2 flex items-center gap-2">
        <MessageSquare size={20} /> Comentarios ({comments?.length || 0})
      </h3>

      <form onSubmit={handleSubmit(onSubmit)} className="shrink-0 flex gap-2 pt-4 border-t border-slate-100 mb-2">
        <input
          {...register('content')}
          placeholder="Escribe un comentario..."
          className="flex-1 rounded-lg border border-slate-300 px-4 py-2 text-sm focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500"
          autoComplete="off"
        />
        <Button type="submit" isLoading={createCommentMutation.isPending} variant="solid" color="primary" radius="lg" size="iconMd" icon={Send} />
      </form>

      <div className="flex-1 min-h-0 flex flex-col gap-4 overflow-y-auto pr-2 scrollbar-thin scrollbar-thumb-slate-200">
        {comments?.map((comment) => (
          <Comment
            key={comment.id}
            comment={comment}
            onRequestHide={() => requestHide(comment.id)}
            onRequestDelete={() => requestDelete(comment.id)}
          />
        ))}
        {comments?.length === 0 && (
          <p className="text-sm text-text-normal text-center py-4 m-auto">Sé el primero en comentar.</p>
        )}
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
    </div>
  );
};

export default CommentBox;