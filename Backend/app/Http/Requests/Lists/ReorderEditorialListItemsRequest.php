<?php

namespace App\Http\Requests\Lists;

use App\Models\ListItem;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

/**
 * Valida el reordenado de una lista editorial: hay que enviar todos sus ítems, cada uno una vez.
 */
class ReorderEditorialListItemsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('update', $this->route('list')) ?? false;
    }

    public function rules(): array
    {
        return [
            'item_ids' => ['required', 'array', 'min:1'],
            'item_ids.*' => ['required', 'uuid', 'distinct'],
        ];
    }

    /**
     * Comprueba que los IDs enviados son exactamente los de la lista (solo si las reglas básicas han pasado).
     */
    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            if ($validator->errors()->isNotEmpty()) {
                return;
            }

            $sent = collect($this->input('item_ids', []))->sort()->values()->all();
            $current = ListItem::query()->where('list_id', $this->route('list')->id)->pluck('item_id')->sort()->values()->all();

            if ($sent !== $current) {
                $validator->errors()->add('item_ids', 'Envía todos los ítems de la lista, cada uno una vez.');
            }
        });
    }

    /**
     * Mensajes de validación personalizados para las reglas.
     */
    public function messages(): array
    {
        return [
            'item_ids.required' => 'El orden de los ítems es obligatorio.',
            'item_ids.array' => 'El orden de los ítems debe ser una lista.',
            'item_ids.min' => 'El orden debe incluir al menos un ítem.',
            'item_ids.*.required' => 'Cada ítem debe tener un identificador.',
            'item_ids.*.uuid' => 'Cada ítem debe ser un identificador válido.',
            'item_ids.*.distinct' => 'Hay ítems repetidos en el orden.',
        ];
    }
}
