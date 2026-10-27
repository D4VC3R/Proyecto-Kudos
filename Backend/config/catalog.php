<?php

return [
    // Calidad de la ficha (F0-05). Los ítems con quality_score por debajo de este umbral (0–100)
    // no salen en la cola de votación ni en los destacados (GET /api/items?discoverable=1).
    'min_quality_for_discovery' => 40,

    // Fragmentos de texto (sin distinguir mayúsculas) que delatan una descripción de plantilla:
    // una descripción que contenga alguno no suma puntos de calidad.
    'template_description_patterns' => ['Item importado', 'importado desde'],

    // Slugs que no puede usar una categoría porque coinciden con una ruta de primer nivel del frontend (F0-09).
    // Se comparan sin distinguir mayúsculas (React Router tampoco las distingue).
    // Al añadir una ruta de primer nivel en Frontend/src/routes/routes.jsx, añádela aquí.
    'reserved_slugs' => [
        // Rutas actuales
        'buscar', 'ranking', 'admin', 'profile', 'my-proposals', 'forbidden', '404',
        'login', 'register', 'verify-email', 'forgot-password', 'password-reset',
        // Rutas previstas (docs/frontend/arquitectura.md §2)
        'wishlist', 'u', 'listas', 'privacidad', 'aviso-legal',
    ],

    // La F1-02 añade el resto (proveedores, límites…).
];
