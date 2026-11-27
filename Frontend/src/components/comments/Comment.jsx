import React, { useState } from 'react';
import { UserCircle, EyeOff, Eye, Trash2, Edit2, Check, X, Star, Reply } from 'lucide-react';
import { formatDate } from '../../lib/formatters';
// Componentes
import Button from '../ui/Button.jsx';
import StorageImage from '../ui/StorageImage.jsx';
import Chip from '../ui/Chip.jsx';
import Textarea from '../ui/Textarea.jsx';
import ReportMenu from '../reports/ReportMenu.jsx';
// Hooks
import { useSessionStore, selectIsVerified } from '../../store/useSessionStore';
import { useAdminUnhideComment } from '../../hooks/admin/useAdminMutations';
import { useUpdateComment } from '../../hooks/items/useItemMutations';

const Comment = ({ comment, itemId, onRequestHide, onRequestDelete, onReply, isReply = false }) => {
  const { user } = useSessionStore();
  const isAdmin = user?.role === 'admin';
  const isOwner = user?.id === comment.user?.id;
  const isVerified = useSessionStore(selectIsVerified);
  const isEdited = comment.updated_at && comment.updated_at !== comment.created_at;

  const { mutate: unhideComment, isPending: isUnhiding } = useAdminUnhideComment();
  const { mutate: updateComment, isPending: isUpdating } = useUpdateComment();

  const [isEditing, setIsEditing] = useState(false);
  const [editContent, setEditContent] = useState(comment.content);

  const author = comment.user;
  // F3-08: el autor borró su cuenta y el comentario se conserva anonimizado (user.id nulo).
  const isDeletedUser = !author?.id;

  const handleUpdate = () => {
    if (editContent.trim() && editContent !== comment.content) {
      updateComment(
        { id: comment.id, itemId: itemId, content: editContent },
        { onSuccess: () => setIsEditing(false) }
      );
    } else {
      setIsEditing(false);
    }
  };

  const ICON_BUTTON = 'min-h-9 w-9 px-0';

  return (
    <div className={`flex gap-3 ${comment.is_hidden ? 'opacity-60' : ''}`}>

      <div className="flex h-8 w-8 shrink-0 items-center justify-center overflow-hidden rounded-full bg-line text-ink-muted">
        <StorageImage src={author?.avatar || author?.profile?.avatar} alt={isDeletedUser ? 'Usuario eliminado' : (author?.name || 'Usuario')} className="w-full h-full object-cover" fallbackIcon={UserCircle} />
      </div>

      <div className="flex-1 min-w-0">
        <div className="flex flex-wrap justify-between items-start gap-2">

          <div className="flex flex-col min-w-0">
            <div className="flex items-center gap-2 flex-wrap">
              <span className="truncate text-small font-semibold text-ink">{isDeletedUser ? 'Usuario eliminado' : (author?.name || 'Usuario')}</span>
              {comment.is_review && (
                <Chip className="shrink-0 gap-1">
                  <Star size={12} aria-hidden="true" className="fill-kudos text-kudos" />
                  Reseña{comment.score != null ? ` · ${comment.score}` : ''}
                </Chip>
              )}
              {isEdited && <span className="shrink-0 text-caption italic text-ink-muted">(editado)</span>}
            </div>
            <span className="text-caption text-ink-muted">{formatDate(comment.created_at)}</span>
          </div>

          <div className="flex items-center gap-1 shrink-0">
            {isOwner && !comment.is_hidden && (
              <Button onClick={() => { setIsEditing(true); setEditContent(comment.content); }} variant="ghost" className={ICON_BUTTON} title="Editar" aria-label="Editar" icon={Edit2} />
            )}
            {isAdmin && !comment.is_hidden && (
              <Button onClick={onRequestHide} variant="ghost" className={`${ICON_BUTTON} text-warning`} title="Ocultar (Moderación)" aria-label="Ocultar (Moderación)" icon={EyeOff} />
            )}
            {isAdmin && comment.is_hidden && (
              <Button onClick={() => unhideComment(comment.id)} disabled={isUnhiding} variant="ghost" className={`${ICON_BUTTON} text-success`} title="Restaurar" aria-label="Restaurar" icon={Eye} />
            )}
            {isAdmin && (
              <Button onClick={onRequestDelete} variant="ghost" className={`${ICON_BUTTON} text-danger`} title="Eliminar" aria-label="Eliminar" icon={Trash2} />
            )}
            {onReply && isVerified && !comment.is_hidden && (
              <Button onClick={onReply} variant="ghost" className={ICON_BUTTON} title="Responder" aria-label="Responder" icon={Reply} />
            )}
            {isVerified && !isOwner && !isAdmin && !comment.is_hidden && !isDeletedUser && (
              <ReportMenu type="comment" id={comment.id} label="comentario" />
            )}
          </div>
        </div>

        {comment.is_hidden && (!isAdmin && !isOwner) ? (
          <div className="mt-1 text-small italic text-warning">
            [Comentario oculto por moderación: {comment.hidden_reason || 'Sin motivo especificado'}]
          </div>
        ) : isEditing ? (
          <div className="mt-2 flex flex-col gap-2">
            <Textarea
              hideLabel
              label="Editar comentario"
              value={editContent}
              onChange={(e) => setEditContent(e.target.value)}
              rows={3}
              disabled={isUpdating}
            />
            <div className="flex gap-2 justify-end">
              <Button onClick={() => setIsEditing(false)} disabled={isUpdating} variant="ghost" className={ICON_BUTTON} icon={X} aria-label="Cancelar edición" />
              <Button onClick={handleUpdate} disabled={isUpdating || !editContent.trim()} variant="ghost" className={ICON_BUTTON} icon={Check} aria-label="Guardar cambios" />
            </div>
          </div>
        ) : (
          <>
            <p className="mt-1 max-w-prose break-words text-small leading-relaxed text-ink">{comment.content}</p>
            {comment.is_hidden && (
              <div className="mt-2 text-caption italic text-warning">
                [Oculto: {comment.hidden_reason || 'Sin motivo'}]
              </div>
            )}
          </>
        )}
      </div>
    </div>
  );
};

export default Comment;