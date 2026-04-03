<?php

namespace App\Services;

use App\Models\CategoryFieldDefinition;
use Carbon\Carbon;

class CategoryExtraDataValidator
{
    /**
     * @param array<string,mixed>|null $inputExtraData
     * @param array<string,mixed> $existingExtraData
     * @return array<string,array<int,string>>
     */
    public function validate(
        string $categoryId,
        ?array $inputExtraData,
        array $existingExtraData = [],
        bool $requireRequiredFields = true,
    ): array {
        $errors = [];
        $payload = $inputExtraData ?? [];

        $definitions = CategoryFieldDefinition::query()
            ->where('category_id', $categoryId)
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->get();

        // Compatibilidad progresiva: si la categoria no define esquema,
        // se permite cualquier estructura de extra_data.
        if ($definitions->isEmpty()) {
            return [];
        }

        $allowedKeys = $definitions->pluck('key')->all();

        foreach ($payload as $key => $value) {
            if (!in_array($key, $allowedKeys, true)) {
                $errors["extra_data.{$key}"][] = 'El campo no esta permitido para esta categoria.';
            }
        }

        $finalData = $existingExtraData;
        foreach ($payload as $key => $value) {
            $finalData[$key] = $value;
        }

        foreach ($definitions as $definition) {
            $key = $definition->key;
            $value = $payload[$key] ?? null;
            $hasKeyInPayload = array_key_exists($key, $payload);

            if ($requireRequiredFields && $definition->required) {
                if (!$this->hasValue($finalData[$key] ?? null)) {
                    $errors["extra_data.{$key}"][] = "El campo '{$key}' es obligatorio para esta categoria.";
                    continue;
                }
            }

            // En updates parciales solo valida campos enviados.
            if (!$hasKeyInPayload) {
                continue;
            }

            if ($value === null) {
                continue;
            }

            foreach ($this->validateType($definition->type, $value, $definition->options ?? []) as $message) {
                $errors["extra_data.{$key}"][] = $message;
            }

            foreach ($this->validateRules($value, $definition->rules ?? []) as $message) {
                $errors["extra_data.{$key}"][] = $message;
            }
        }

        return $errors;
    }

    private function hasValue(mixed $value): bool
    {
        if ($value === null) {
            return false;
        }

        if (is_string($value) && trim($value) === '') {
            return false;
        }

        if (is_array($value) && $value === []) {
            return false;
        }

        return true;
    }

    /**
     * @param array<int,mixed>|mixed $options
     * @return array<int,string>
     */
    private function validateType(string $type, mixed $value, mixed $options): array
    {
        return match ($type) {
            'string', 'text' => is_string($value)
                ? []
                : ['Debe ser una cadena de texto.'],
            'integer' => is_int($value)
                ? []
                : ['Debe ser un numero entero.'],
            'decimal' => is_numeric($value)
                ? []
                : ['Debe ser un numero decimal.'],
            'boolean' => is_bool($value)
                ? []
                : ['Debe ser true o false.'],
            'url' => is_string($value) && filter_var($value, FILTER_VALIDATE_URL)
                ? []
                : ['Debe ser una URL valida.'],
            'email' => is_string($value) && filter_var($value, FILTER_VALIDATE_EMAIL)
                ? []
                : ['Debe ser un email valido.'],
            'date' => $this->isValidDate($value)
                ? []
                : ['Debe ser una fecha valida.'],
            'enum' => $this->validateEnum($value, $options),
            'multiselect' => $this->validateMultiselect($value, $options),
            'string_list' => $this->validateStringList($value),
            'json' => is_array($value)
                ? []
                : ['Debe ser un objeto o arreglo JSON valido.'],
            default => [],
        };
    }

    /**
     * @param array<int,mixed>|mixed $options
     * @return array<int,string>
     */
    private function validateEnum(mixed $value, mixed $options): array
    {
        if (!is_array($options) || $options === []) {
            return [];
        }

        return in_array($value, $options, true)
            ? []
            : ['Debe ser uno de los valores permitidos.'];
    }

    /**
     * @param array<int,mixed>|mixed $options
     * @return array<int,string>
     */
    private function validateMultiselect(mixed $value, mixed $options): array
    {
        if (!is_array($value)) {
            return ['Debe ser un arreglo de valores.'];
        }

        if (!is_array($options) || $options === []) {
            return [];
        }

        foreach ($value as $entry) {
            if (!in_array($entry, $options, true)) {
                return ['Contiene valores fuera de las opciones permitidas.'];
            }
        }

        return [];
    }

    /**
     * @return array<int,string>
     */
    private function validateStringList(mixed $value): array
    {
        if (!is_array($value)) {
            return ['Debe ser un arreglo de cadenas de texto.'];
        }

        foreach ($value as $entry) {
            if (!is_string($entry)) {
                return ['Todos los elementos deben ser cadenas de texto.'];
            }
        }

        return [];
    }

    /**
     * @param array<string,mixed>|mixed $rules
     * @return array<int,string>
     */
    private function validateRules(mixed $value, mixed $rules): array
    {
        if (!is_array($rules) || $rules === []) {
            return [];
        }

        $messages = [];

        if (isset($rules['min']) && is_numeric($value) && $value < $rules['min']) {
            $messages[] = 'El valor es menor al minimo permitido.';
        }

        if (isset($rules['max']) && is_numeric($value) && $value > $rules['max']) {
            $messages[] = 'El valor supera el maximo permitido.';
        }

        if (isset($rules['min_length']) && is_string($value) && mb_strlen($value) < (int) $rules['min_length']) {
            $messages[] = 'La longitud es menor a la minima permitida.';
        }

        if (isset($rules['max_length']) && is_string($value) && mb_strlen($value) > (int) $rules['max_length']) {
            $messages[] = 'La longitud supera la maxima permitida.';
        }

        if (isset($rules['max_items']) && is_array($value) && count($value) > (int) $rules['max_items']) {
            $messages[] = 'El numero de elementos supera el maximo permitido.';
        }

        if (isset($rules['min_items']) && is_array($value) && count($value) < (int) $rules['min_items']) {
            $messages[] = 'El numero de elementos es menor al minimo permitido.';
        }

        if (
            isset($rules['regex'])
            && is_string($value)
            && @preg_match((string) $rules['regex'], $value) !== 1
        ) {
            $messages[] = 'El valor no cumple el formato requerido.';
        }

        return $messages;
    }

    private function isValidDate(mixed $value): bool
    {
        if (!is_string($value)) {
            return false;
        }

        try {
            Carbon::parse($value);
            return true;
        } catch (\Throwable) {
            return false;
        }
    }
}


