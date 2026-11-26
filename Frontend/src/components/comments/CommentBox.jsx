import React, { useState } from 'react';
import { Loader2, MessageSquare, Send, X } from 'lucide-react';
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
import { useSessionStore, selectIsVerified } from '../../store/useSessionStore';

const CommentBox = ({ itemId, userVote, className = "" }) => {
  const { data: comments, isLoading } = useItemComments(itemId);
  const createCommentMutation = useCreateComment();
  const { mutate: hideComment, isPending: isHiding } = useAdminHideComment();
  const { mutate: deleteComment, isPending: isDeleting } = useDeleteComment();

  const { register, handleSubmit, reset } = useForm();

  const { isOpen, modalType, openModal, closeModal } = useModal();
  const [activeCommentId, setActiveCommentId] = useState(null);
  const [hideReason, setHideReason] = useState('');
  const [isReview, setIsReview] = useState(false);
  const [replyingTo, setReplyingTo] = useState(null);
  const [replyContent, setReplyContent] = useState('');

  const { user } = useSessionStore();
  const isVerified = useSessionStore(selectIsVerified);
  // Reseña (F3-02): solo con una nota (voto "vote") y si el usuario aún no tiene una reseña en la lista.
  const hasOwnReview = comments?.some((c) => c.is_review && c.user?.id === user?.id);
  const canReview = isVerified && userVote?.type === 'vote' && !hasOwnReview;

  const onSubmit = (data) => {
    if (!data.content.trim()) return;
    createCommentMutation.mutate(
      { itemId, content: data.content, isReview: canReview && isReview },
      { onSuccess: () => { reset(); setIsReview(false); } }
    );
  };

  const submitReply = (parentId) => {
    if (!replyContent.trim()) return;
    createCommentMutation.mutate(
      { itemId, content: replyContent, parentId },
      { onSuccess: () => { setReplyContent(''); setReplyingTo(null); } }
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
      deleteComment({ id: activeCommentId, itemId }, { onSuccess: closeModal });
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
          aria-label="Escribe un comentario"
          className="flex-1 rounded-lg border border-slate-300 px-4 py-2 text-sm focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500"
          autoComplete="off"
        />
        <Button type="submit" isLoading={createCommentMutation.isPending} variant="solid" color="primary" radius="lg" size="iconMd" icon={Send} aria-label="Publicar comentario" />
      </form>

      {canReview && (
        <label className="shrink-0 flex items-center gap-2 text-xs text-text-normal mb-2 cursor-pointer">
          <input type="checkbox" checked={isReview} onChange={(e) => setIsReview(e.target.checked)} className="accent-blue-600" />
          Publicar como reseña (tu nota: {userVote.score})
        </label>
      )}

      <div className="flex-1 min-h-0 flex flex-col gap-4 overflow-y-auto pr-2">
        {comments?.map((comment) => (
          <div key={comment.id} className="flex flex-col gap-2">
            <Comment
              itemId={itemId}
              comment={comment}
              onRequestHide={() => requestHide(comment.id)}
              onRequestDelete={() => requestDelete(comment.id)}
              onReply={() => { setReplyingTo(replyingTo === comment.id ? null : comment.id); setReplyContent(''); }}
            />
            {replyingTo === comment.id && (
              <form
                onSubmit={(e) => { e.preventDefault(); submitReply(comment.id); }}
                className="ml-6 sm:ml-10 flex gap-2"
              >
                <input
                  value={replyContent}
                  onChange={(e) => setReplyContent(e.target.value)}
                  placeholder="Escribe una respuesta..."
                  aria-label="Escribe una respuesta"
                  maxLength={2000}
                  autoFocus
                  className="flex-1 min-w-0 rounded-lg border border-slate-300 px-4 py-2 text-sm focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500"
                  autoComplete="off"
                />
                <Button type="submit" isLoading={createCommentMutation.isPending} variant="solid" color="primary" radius="lg" size="iconMd" icon={Send} aria-label="Enviar respuesta" />
                <Button type="button" onClick={() => setReplyingTo(null)} variant="ghost" color="neutral" radius="lg" size="iconMd" icon={X} title="Cancelar" aria-label="Cancelar respuesta" />
              </form>
            )}
            {comment.replies?.length > 0 && (
              <div className="ml-6 sm:ml-10 flex flex-col gap-2 border-l-2 border-slate-100 pl-3">
                {comment.replies.map((reply) => (
                  <Comment
                    key={reply.id}
                    itemId={itemId}
                    comment={reply}
                    isReply
                    onRequestHide={() => requestHide(reply.id)}
                    onRequestDelete={() => requestDelete(reply.id)}
                  />
                ))}
              </div>
            )}
          </div>
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