<?php

namespace App\Http\Requests\Admin;

use App\Models\Report;
use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Valida la resolución de un reporte por parte de un administrador (F3-04).
 */
class ResolveReportRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('resolve', $this->route('report')) ?? false;
    }

    public function rules(): array
    {
        return [
            'action' => ['required', 'string', Rule::in(Report::ACTIONS)],
            'note' => ['nullable', 'string', 'max:1000', 'required_unless:action,'.Report::ACTION_DISMISS],
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            $report = $this->route('report');

            if ($this->input('action') === Report::ACTION_HIDE && $report?->reportable_type === User::class) {
                $validator->errors()->add('action', 'Un perfil no se puede ocultar: usa «Eliminar» para vaciar su contenido público.');
            }
        });
    }

    /**
     * Mensajes de validación personalizados para las reglas.
     */
    public function messages(): array
    {
        return [
            'action.required' => 'Elige una acción.',
            'action.string' => 'La acción no es válida.',
            'action.in' => 'La acción seleccionada no es válida.',
            'note.string' => 'La nota debe ser texto.',
            'note.max' => 'La nota no puede superar los 1000 caracteres.',
            'note.required_unless' => 'Escribe el motivo: se le enviará al autor.',
        ];
    }
}
