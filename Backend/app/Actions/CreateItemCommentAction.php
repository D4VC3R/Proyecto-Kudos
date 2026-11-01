<?php

namespace App\Actions;

use App\Models\Item;
use App\Models\ItemComment;
use App\Models\User;
use App\Models\Vote;
use App\Notifications\CommentRepliedNotification;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Crea un comentario, una respuesta (un nivel) o una reseña (F3-02) y avisa al autor del comentario respondido.
 */
class CreateItemCommentAction
{
    /**
     * @throws ValidationException si otra petición simultánea ya creó la reseña (índice único item_comments_review_unique).
     */
    public function execute(Item $item, User $author, string $content, ?ItemComment $parent = null, ?Vote $reviewVote = null): ItemComment
    {
        try {
            return DB::transaction(function () use ($item, $author, $content, $parent, $reviewVote) {
                $comment = ItemComment::create([
                    'item_id' => $item->id,
                    'user_id' => $author->id,
                    'content' => $content,
                    'parent_id' => $parent?->id,
                    'vote_id' => $reviewVote?->id,
                ]);

                // No se avisa a quien se responde a sí mismo. Si el autor del padre ya no existe (F3-08), tampoco.
                if ($parent && $parent->user_id !== $author->id) {
                    $parent->user?->notify(new CommentRepliedNotification($comment));
                }

                return $comment;
            });
        } catch (UniqueConstraintViolationException) {
            throw ValidationException::withMessages(['is_review' => [ItemComment::REVIEW_DUPLICATE_MESSAGE]]);
        }
    }
}
