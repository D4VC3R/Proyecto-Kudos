import { useState } from 'react';
import { useAdminCommentsContext } from '../../hooks/admin/useAdminContexts';
import { AdminCommentHideForm } from './AdminCommentHideForm';

const formatAuthor = (comment) => {
  return comment?.user?.name ?? 'Usuario';
};

export const AdminCommentsTable = () => {
  const { comments, isMutatingComments, hideComment, unhideComment } = useAdminCommentsContext();
  const [activeHideCommentId, setActiveHideCommentId] = useState(null);

  return (
    <div className="overflow-x-auto rounded-xl border border-slate-800 bg-slate-900">
      <table className="min-w-full text-left text-sm">
        <thead className="border-b border-slate-800 text-slate-300">
          <tr>
            <th className="px-4 py-3">Comentario</th>
            <th className="px-4 py-3">Autor</th>
            <th className="px-4 py-3">Estado</th>
            <th className="px-4 py-3">Accion</th>
          </tr>
        </thead>

        <tbody>
          {comments.map((comment) => {
            const isHideFormVisible = activeHideCommentId === comment.id;
            const hideForm = isHideFormVisible ? (
              <AdminCommentHideForm
                isMutating={isMutatingComments}
                onCancel={() => setActiveHideCommentId(null)}
                onConfirm={async ({ reason }) => {
                  await hideComment({ commentId: comment.id, reason });
                  setActiveHideCommentId(null);
                }}
              />
            ) : null;

            return (
              <tr className="border-b border-slate-800/70 align-top last:border-0" key={comment.id}>
                <td className="px-4 py-3 text-slate-100">{comment.content}</td>
                <td className="px-4 py-3 text-slate-300">{formatAuthor(comment)}</td>
                <td className="px-4 py-3 text-slate-300">{comment.is_hidden ? 'Oculto' : 'Visible'}</td>
                <td className="px-4 py-3">
                  {!comment.is_hidden && (
                    <button
                      className="rounded-md bg-red-700 px-2 py-1 text-xs font-medium text-white hover:bg-red-600 disabled:cursor-not-allowed disabled:opacity-60"
                      disabled={isMutatingComments}
                      onClick={() => setActiveHideCommentId(comment.id)}
                      type="button"
                    >
                      Ocultar
                    </button>
                  )}

                  {comment.is_hidden && (
                    <button
                      className="rounded-md bg-emerald-700 px-2 py-1 text-xs font-medium text-white hover:bg-emerald-600 disabled:cursor-not-allowed disabled:opacity-60"
                      disabled={isMutatingComments}
                      onClick={() => unhideComment({ commentId: comment.id })}
                      type="button"
                    >
                      Restaurar
                    </button>
                  )}

                  {hideForm}
                </td>
              </tr>
            );
          })}
        </tbody>
      </table>
    </div>
  );
};
