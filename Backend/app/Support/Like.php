<?php

namespace App\Support;

/**
 * Construye patrones LIKE/ILIKE de PostgreSQL a partir de texto del usuario (F0-15).
 * PostgreSQL usa '\' como carácter de escape por defecto en LIKE/ILIKE: basta con escapar
 * '\', '%' y '_' para que se busquen de forma literal.
 */
final class Like
{
    /**
     * Escapa '\', '%' y '_'. La barra invertida va primero para no duplicar los escapes añadidos.
     */
    public static function escape(string $value): string
    {
        return str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], $value);
    }

    /**
     * Patrón «contiene» ('%valor%') con el valor escapado.
     */
    public static function contains(string $value): string
    {
        return '%' . self::escape($value) . '%';
    }
}
