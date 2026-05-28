import React, { useState } from 'react';
import { UserCircle, EyeOff, Eye, Trash2, Edit2, Check, X } from 'lucide-react';
import { formatDate } from '../../lib/formatters';
import { useSessionStore } from '../../store/useSessionStore';
import { useAdminUnhideComment } from '../../hooks/admin/useAdminMutations';
import { useUpdateComment } from '../../hooks/items/useItemMutations';
import { Button } from '../common/Button';
import StorageImage from '../common/StorageImage.jsx';
import { TextAreaField } from '../common/TextAreaField.jsx';

export const Comment = ({ comment, onRequestHide, onRequestDelete }) => {
  const { user } = useSessionStore();
  const isAdmin = user?.role === 'admin';
  const isOwner = user?.id === comment.user?.id;
  const isEdited = comment.updated_at && comment.updated_at !== comment.created_at;

  const { mutate: unhideComment, isPending: isUnhiding } = useAdminUnhideComment();
  const { mutate: updateComment, isPending: isUpdating } = useUpdateComment();

  const [isEditing, setIsEditing] = useState(false);
  const [editContent, setEditContent] = useState(comment.content);

  const author = comment.user;

  const handleUpdate = () => {
    if (editContent.trim() && editContent !== comment.content) {
      updateComment({ id: comment.id, content: editContent }, { onSuccess: () => setIsEditing(false) });
    } else {
      setIsEditing(false);
    }
  };

  return (
    <div className={`p-4 rounded-xl border flex gap-3 transition-colors ${comment.is_hidden ? 'bg-background border-border opacity-60' : 'bg-background border-slate-100'}`}>

      <div className="shrink-0 flex items-center justify-center w-10 h-10 rounded-full bg-slate-200 text-text-normal overflow-hidden">
        <StorageImage src={author?.avatar || author?.profile?.avatar} alt={author?.name || 'Usuario'} className="w-full h-full object-cover" fallbackIcon={UserCircle} />
      </div>

      <div className="flex-1 min-w-0">
        <div className="flex flex-wrap justify-between items-start gap-2 mb-2">

          <div className="flex flex-col min-w-0">
            <div className="flex items-center gap-2 flex-wrap">
              <span className="font-bold text-sm text-slate-800 truncate">{author?.name || 'Usuario'}</span>
              {isEdited && <span className="text-[10px] text-slate-400 italic shrink-0">(editado)</span>}
            </div>
            <span className="text-xs text-slate-400">{formatDate(comment.created_at)}</span>
          </div>

          <div className="flex items-center gap-1 shrink-0">
            {isOwner && !comment.is_hidden && (
              <Button onClick={() => { setIsEditing(true); setEditContent(comment.content); }} variant="ghost" color="primary" size="iconSm" title="Editar" icon={Edit2} />
            )}
            {isAdmin && !comment.is_hidden && (
              <Button onClick={onRequestHide} variant="ghost" color="warning" size="iconSm" title="Ocultar (Moderación)" icon={EyeOff} />
            )}
            {isAdmin && comment.is_hidden && (
              <Button onClick={() => unhideComment(comment.id)} disabled={isUnhiding} variant="ghost" color="success" size="iconSm" title="Restaurar" icon={Eye} />
            )}
            {isAdmin && (
              <Button onClick={onRequestDelete} variant="ghost" color="danger" size="iconSm" title="Eliminar" icon={Trash2} />
            )}
          </div>
        </div>

        {comment.is_hidden && (!isAdmin && !isOwner) ? (
          <div className="text-sm mt-1 text-orange-700 italic">
            [Comentario oculto por moderación: {comment.hidden_reason || 'Sin motivo especificado'}]
          </div>
        ) : isEditing ? (
          <div className="mt-2 flex flex-col gap-2">
            <TextAreaField
              value={editContent}
              onChange={(e) => setEditContent(e.target.value)}
              rows={3}
              disabled={isUpdating}
              className="w-full resize-none rounded-xl border border-border bg-background py-2 px-3 text-sm text-text-highlight focus:border-blue-500 focus:bg-surface focus:outline-none focus:ring-1 focus:ring-blue-500"
            />
            <div className="flex gap-2 justify-end">
              <Button onClick={() => setIsEditing(false)} disabled={isUpdating} variant="ghost" color="neutral" size="iconSm" icon={X} />
              <Button onClick={handleUpdate} disabled={isUpdating || !editContent.trim()} variant="ghost" color="primary" size="iconSm" icon={Check} />
            </div>
          </div>
        ) : (
          <>
            <p className="text-sm text-slate-700 mt-1 break-words">{comment.content}</p>
            {comment.is_hidden && (
              <div className="text-xs mt-2 text-orange-600 italic">
                [Oculto: {comment.hidden_reason || 'Sin motivo'}]
              </div>
            )}
          </>
        )}
      </div>
    </div>
  );
};