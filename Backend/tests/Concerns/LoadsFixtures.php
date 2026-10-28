<?php

namespace Tests\Concerns;

/**
 * Carga las respuestas recortadas de APIs externas de tests/Fixtures (docs/backend/testing.md §5).
 */
trait LoadsFixtures
{
    /** @return array<mixed> el JSON de tests/Fixtures/{$path}, decodificado */
    protected function fixture(string $path): array
    {
        return json_decode((string) file_get_contents(base_path('tests/Fixtures/'.$path)), true, 512, JSON_THROW_ON_ERROR);
    }
}
