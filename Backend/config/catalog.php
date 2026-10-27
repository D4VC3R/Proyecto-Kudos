<?php

return [
    // Calidad de la ficha (F0-05). Los ítems con quality_score por debajo de este umbral (0–100)
    // no salen en la cola de votación ni en los destacados (GET /api/items?discoverable=1).
    'min_quality_for_discovery' => 40,

    // Fragmentos de texto (sin distinguir mayúsculas) que delatan una descripción de plantilla:
    // una descripción que contenga alguno no suma puntos de calidad.
    'template_description_patterns' => ['Item importado', 'importado desde'],

    // La F0-09 añade 'reserved_slugs' y la F1-02 el resto (proveedores, límites…).
];
