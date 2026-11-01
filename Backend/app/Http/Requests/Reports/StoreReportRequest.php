<?php

namespace App\Http\Requests\Reports;

use App\Models\ItemComment;
use App\Models\Report;
use App\Models\User;
use App\Models\UserList;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Valida el reporte de un contenido (F3-04, DSA): comentario, lista pública o perfil.
 */
class StoreReportRequest extends FormRequest
{
    private ?Model $reportable = null;

    private bool $reportableResolved = false;

    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'reportable_type' => ['required', 'string', Rule::in(array_keys(Report::REPORTABLE_TYPES))],
            'reportable_id' => ['required', 'uuid'],
            'reason' => ['required', 'string', Rule::in(Report::REASONS)],
            'details' => ['nullable', 'string', 'max:1000', 'required_if:reason,otro'],
        ];
    }

    /**
     * El contenido reportado, o null si no existe (o es una lista privada). Se resuelve una sola vez.
     */
    public function reportable(): ?Model
    {
        if ($this->reportableResolved) {
            return $this->reportable;
        }

        $this->reportableResolved = true;
        $id = (string) $this->input('reportable_id');

        $this->reportable = match ($this->input('reportable_type')) {
            'comment' => ItemComment::query()->find($id),
            'list' => UserList::query()->where('visibility', '!=', UserList::VISIBILITY_PRIVATE)->find($id),
            'user' => User::query()->find($id),
            default => null,
        };

        return $this->reportable;
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            if ($validator->errors()->isNotEmpty()) {
                return;
            }

            $reportable = $this->reportable();

            if ($reportable === null) {
                $validator->errors()->add('reportable_id', 'El contenido que intentas reportar no existe.');

                return;
            }

            if (Report::authorIdOf($reportable) === $this->user()->id) {
                $validator->errors()->add('reportable_id', 'No puedes reportar tu propio contenido.');
            }
        });
    }

    /**
     * Mensajes de validación personalizados para las reglas.
     */
    public function messages(): array
    {
        return [
            'reportable_type.required' => 'Indica qué tipo de contenido quieres reportar.',
            'reportable_type.string' => 'El tipo de contenido no es válido.',
            'reportable_type.in' => 'El tipo de contenido no es válido.',
            'reportable_id.required' => 'Indica qué contenido quieres reportar.',
            'reportable_id.uuid' => 'El identificador del contenido no es válido.',
            'reason.required' => 'Elige un motivo.',
            'reason.string' => 'El motivo no es válido.',
            'reason.in' => 'El motivo seleccionado no es válido.',
            'details.string' => 'Los detalles deben ser texto.',
            'details.max' => 'Los detalles no pueden superar los 1000 caracteres.',
            'details.required_if' => 'Cuéntanos el motivo si eliges «Otro».',
        ];
    }
}
