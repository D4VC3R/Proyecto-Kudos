<?php

namespace App\Http\Requests\Items;

use App\Models\ItemComment;
use App\Models\Vote;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

/**
 * Valida la creación de comentarios, respuestas y reseñas (F3-02).
 */
class StoreItemCommentRequest extends FormRequest
{
    private ?ItemComment $parentComment = null;

    private ?Vote $reviewVote = null;

    /** Verifica que el usuario tenga permiso para crear un comentario en el ítem específico.
     * - El permiso se verifica utilizando la política de autorización 'create' para el modelo de comentario, pasando el ítem como contexto.
     * - Si el usuario no tiene permiso, la petición será denegada automáticamente.
     */
    public function authorize(): bool
    {
        return $this->user()?->can('create', [ItemComment::class, $this->route('item')]) ?? false;
    }

    /** Define las reglas de validación para crear un comentario en un ítem.
     * - El campo 'content' es obligatorio y debe tener una longitud mínima de 2 caracteres, (máxima de 2000).
     * - Estas reglas aseguran que el contenido del comentario sea adecuado y no demasiado corto ni excesivamente largo.
     */
    public function rules(): array
    {
        return [
            'content' => ['required', 'string', 'min:2', 'max:2000'],
            'parent_id' => ['sometimes', 'nullable', 'uuid'],
            'is_review' => ['sometimes', 'boolean'],
        ];
    }

    /**
     * Mensajes de validación personalizados para las reglas.
     */
    public function messages(): array
    {
        return [
            'content.required' => 'El campo contenido es obligatorio.',
            'content.string' => 'El campo contenido debe ser texto.',
            'content.min' => 'El campo contenido debe tener al menos 2 caracteres/elementos.',
            'content.max' => 'El campo contenido no puede superar los 2000 caracteres/elementos.',
            'parent_id.uuid' => 'El comentario al que respondes no es válido.',
            'is_review.boolean' => 'El campo reseña debe ser verdadero o falso.',
        ];
    }

    /**
     * Comprobaciones con base de datos (F3-02):
     * - parent_id: el padre existe (sin borrar), es del mismo ítem, no está oculto y no es a su vez una respuesta (un solo nivel).
     * - is_review: el usuario tiene un voto "vote" en el ítem y aún no tiene una reseña viva de ese ítem. Una respuesta no puede ser reseña.
     */
    public function after(): array
    {
        return [
            function (Validator $validator) {
                if ($validator->errors()->isNotEmpty()) {
                    return;
                }

                $item = $this->route('item');
                $parentId = $this->input('parent_id');
                $isReview = $this->boolean('is_review');

                if ($parentId !== null) {
                    if ($isReview) {
                        $validator->errors()->add('is_review', 'Una respuesta no puede ser una reseña.');

                        return;
                    }

                    $parent = ItemComment::query()->find($parentId);

                    if (! $parent || $parent->item_id !== $item->id) {
                        $validator->errors()->add('parent_id', 'El comentario al que respondes no existe en este ítem.');
                    } elseif ($parent->parent_id !== null) {
                        $validator->errors()->add('parent_id', 'Solo puedes responder a un comentario principal, no a una respuesta.');
                    } elseif ($parent->is_hidden) {
                        $validator->errors()->add('parent_id', 'No puedes responder a un comentario oculto.');
                    } else {
                        $this->parentComment = $parent;
                    }
                }

                if ($isReview) {
                    $vote = Vote::query()
                        ->where('user_id', $this->user()->id)
                        ->where('item_id', $item->id)
                        ->where('type', Vote::TYPE_VOTE)
                        ->first();

                    if (! $vote) {
                        $validator->errors()->add('is_review', 'Para escribir una reseña tienes que valorar antes este ítem con una nota.');
                    } elseif (ItemComment::query()->where('item_id', $item->id)->where('user_id', $this->user()->id)->whereNotNull('vote_id')->exists()) {
                        $validator->errors()->add('is_review', ItemComment::REVIEW_DUPLICATE_MESSAGE);
                    } else {
                        $this->reviewVote = $vote;
                    }
                }
            },
        ];
    }

    /** Comentario padre validado (null si no es una respuesta). */
    public function parentComment(): ?ItemComment
    {
        return $this->parentComment;
    }

    /** Voto que convierte el comentario en reseña (null si no es una reseña). */
    public function reviewVote(): ?Vote
    {
        return $this->reviewVote;
    }
}
