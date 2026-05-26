import React from 'react';
import { Loader2, MessageSquare, Send } from 'lucide-react';
import { useForm } from 'react-hook-form';
import { useItemComments } from '../../hooks/items/useItemQueries';
import { useCreateComment } from '../../hooks/items/useItemMutations';
import { Comment } from './Comment';
import { Button } from '../common/Button';

export const CommentBox = ({ itemId }) => {
  const { data: comments, isLoading } = useItemComments(itemId);
  const createCommentMutation = useCreateComment();
  const { register, handleSubmit, reset } = useForm();

  const onSubmit = (data) => {
    if (!data.content.trim()) return;
    createCommentMutation.mutate(
      { itemId, content: data.content },
      {
        onSuccess: () => reset()
      }
    );
  };

  if (isLoading) {
    return <div className="flex justify-center p-4"><Loader2 className="animate-spin text-blue-500" /></div>;
  }

  return (
    <div className="mt-6 border-t border-slate-200 pt-6 w-full text-left">
      <h3 className="text-lg font-bold text-slate-900 mb-4 flex items-center gap-2">
        <MessageSquare size={20} /> Comentarios ({comments?.length || 0})
      </h3>

      <div className="flex flex-col gap-4 max-h-60 overflow-y-auto mb-4 pr-2 scrollbar-thin scrollbar-thumb-slate-200">
        {comments?.map((comment) => (
          <Comment key={comment.id} comment={comment} />
        ))}
        {comments?.length === 0 && (
          <p className="text-sm text-slate-500 text-center py-4">Sé el primero en comentar.</p>
        )}
      </div>

      <form onSubmit={handleSubmit(onSubmit)} className="flex gap-2">
        <input
          {...register('content')}
          placeholder="Escribe un comentario..."
          className="flex-1 rounded-lg border border-slate-300 px-4 py-2 text-sm focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500"
          autoComplete="off"
        />
        <Button
          type="submit"
          isLoading={createCommentMutation.isPending}
          variant="solid"
          color="primary"
          radius="lg"
          size="iconMd"
          icon={Send}
        />
      </form>
    </div>
  );
};
