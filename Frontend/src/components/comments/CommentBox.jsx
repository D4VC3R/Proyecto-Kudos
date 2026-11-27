import React, { useState } from 'react';
import { Loader2, Send, X } from 'lucide-react';
// Componentes
import Modal  from '../ui/Modal.jsx';
import CommentHideBody from "./CommentHideBody.jsx";
import CommentDeleteBody from "./CommentDeleteBody.jsx";
import ModalButtons from "../ui/ModalButtons.jsx";
import Comment from './Comment';
import Button from '../ui/Button.jsx';
import Input from '../ui/Input.jsx';
import Textarea from '../ui/Textarea.jsx';
import SectionHead from '../ui/SectionHead.jsx';
import Checkbox from '../ui/Checkbox.jsx';
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
    <section aria-labelledby="item-comments" className={className}>
      <SectionHead
        id="item-comments"
        title={<>Comentarios <span className="tabular-nums">({comments?.length || 0})</span></>}
      />

      <form onSubmit={handleSubmit(onSubmit)} className="mb-2 mt-4 grid gap-2">
        <Textarea
          label="Escribe un comentario"
          registration={register('content')}
          placeholder="Escribe un comentario..."
          rows={3}
          autoComplete="off"
        />
        <div className="flex flex-wrap items-center justify-between gap-2">
          {canReview ? (
            <Checkbox
              label={`Publicar como reseña (tu nota: ${userVote.score})`}
              checked={isReview}
              onChange={(e) => setIsReview(e.target.checked)}
            />
          ) : (
            <span />
          )}
          <Button type="submit" isLoading={createCommentMutation.isPending} icon={Send}>Publicar</Button>
        </div>
      </form>

      <ul>
        {comments?.map((comment) => (
          <li key={comment.id} className="border-t border-line py-4">
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
                className="ml-6 mt-3 flex gap-2 sm:ml-10"
              >
                <Input
                  hideLabel
                  label="Escribe una respuesta"
                  value={replyContent}
                  onChange={(e) => setReplyContent(e.target.value)}
                  placeholder="Escribe una respuesta..."
                  maxLength={2000}
                  autoFocus
                  className="min-w-0 flex-1"
                  autoComplete="off"
                />
                <Button type="submit" isLoading={createCommentMutation.isPending} icon={Send} className="w-11 px-0" aria-label="Enviar respuesta" />
                <Button type="button" onClick={() => setReplyingTo(null)} variant="ghost" icon={X} className="w-11 px-0" title="Cancelar" aria-label="Cancelar respuesta" />
              </form>
            )}
            {comment.replies?.length > 0 && (
              <div className="ml-6 mt-3 flex flex-col gap-3 border-l border-line pl-4">
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
          </li>
        ))}
      </ul>
      {comments?.length === 0 && (
        <p className="py-4 text-small text-ink-muted">Sé el primero en comentar.</p>
      )}

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
    </section>
  );
};

export default CommentBox;