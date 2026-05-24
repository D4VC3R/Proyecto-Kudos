<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Http\UploadedFile;

class FileOrUrlRule implements ValidationRule
{
    private const MAX_BYTES = 5_242_880;

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if ($value instanceof UploadedFile) {
            $this->validateFile($value, $fail);
            return;
        }

        if (is_string($value) && filter_var($value, FILTER_VALIDATE_URL)) {
            return;
        }

        $fail('El archivo debe ser una imagen valida o una URL.');
    }

    private function validateFile(UploadedFile $file, Closure $fail): void
    {
        if (!$file->isValid()) {
            $fail('La imagen subida no es valida.');
            return;
        }

        if ($file->getSize() > self::MAX_BYTES) {
            $fail('La imagen supera el tamaño máximo permitido.');
            return;
        }

        $mimeType = strtolower((string) $file->getMimeType());
        $allowed = ['image/jpeg', 'image/png', 'image/webp'];

        if (!in_array($mimeType, $allowed, true)) {
            $fail('La imagen debe ser jpeg, png o webp.');
        }
    }
}

