<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Http\UploadedFile;

/**
 * Regla de validación personalizada que acepta tanto archivos subidos como URLs.
 * Valida que el valor sea una URL válida o un archivo subido que cumpla con los requisitos de tamaño y tipo.
 */
class FileOrUrlRule implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (is_string($value) && filter_var($value, FILTER_VALIDATE_URL)) {
            return;
        }

        if ($value instanceof UploadedFile) {
            if (!$value->isValid()) {
                $fail('La imagen subida no es válida.');
                return;
            }

            if ($value->getSize() > config('media.max_upload_size')) {
                $fail('La imagen supera el tamaño máximo permitido.');
                return;
            }

            if (!in_array(strtolower((string) $value->getMimeType()), config('media.allowed_mimes'), true)) {
                $fail('La imagen debe ser jpeg, png o webp.');
            }

            return;
        }

        $fail('El archivo debe ser una imagen válida o una URL.');
    }
}
