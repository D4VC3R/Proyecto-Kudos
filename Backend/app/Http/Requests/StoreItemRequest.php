<?php

namespace App\Http\Requests;

use App\Models\Item;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;

class StoreItemRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return Auth::user()->can('create', Item::class);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
		public function rules(): array
		{
			return [
				'name' => ['required', 'string', 'max:255'],
				'description' => ['required', 'string', 'min:20', 'max:2000'],
				'images' => ['nullable', 'array', 'max:10'],
                'images.*.path' => ['required', 'string', 'max:500'],
                'images.*.disk' => ['required', 'string', 'in:public'],
                'images.*.alt' => ['nullable', 'string', 'max:255'],
                'images.*.order' => ['nullable', 'integer', 'min:0'],
				'category_id' => ['required', 'uuid', 'exists:categories,id'],
				'tag_ids' => ['nullable', 'array', 'max:5'],
				'tag_ids.*' => ['uuid', 'exists:tags,id'],
			];
		}
		public function messages(): array
		{
			return [
				'name.required' => 'El nombre del item es obligatorio.',
				'name.max' => 'El nombre no puede exceder 255 caracteres.',
				'description.required' => 'La descripción es obligatoria.',
				'description.min' => 'La descripción debe tener al menos 20 caracteres.',
				'description.max' => 'La descripción no puede exceder 2000 caracteres.',
                'images.*.path.required' => 'La imagen es obligatoria.',
				'category_id.required' => 'Debes seleccionar una categoría.',
				'category_id.exists' => 'La categoría seleccionada no existe.',
				'tag_ids.max' => 'No puedes seleccionar más de 5 tags.',
				'tag_ids.*.exists' => 'Uno o más tags seleccionados no existen.',
			];
		}
}
