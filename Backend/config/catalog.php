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

    // Las variables de entorno van vacías en los .env*.example (R9) y env() devuelve '' si existen vacías:
    // por eso se usa "?:" en lugar del segundo argumento de env().

    // User-Agent identificativo de todas las peticiones a fuentes externas (fuentes-externas §1.3).
    // Sin él, Wikimedia baja a 10 peticiones por minuto. En producción, con un correo de contacto real.
    'user_agent' => env('CATALOG_USER_AGENT') ?: 'KudosBot/1.0 (+https://kudos.dcerdan.es)',

    // Máximo de ítems por categoría en cada catalog:fetch-new (50 en local, 300 en producción).
    'import_limit' => (int) (env('CATALOG_IMPORT_LIMIT') ?: 50),

    // catalog:refresh vuelve a pedir los ítems sincronizados hace más de estos días.
    'refresh_older_than_days' => 150,

    // Términos de TMDB: nada de TMDB se guarda más de 6 meses sin refrescar (catalog:purge-stale).
    'tmdb_max_age_days' => 180,

    // Cliente HTTP común (macro Http::catalog(), App\Providers\CatalogServiceProvider).
    'http' => [
        'timeout_seconds' => 10,
        // Intentos en total (el primero incluido); solo se reintenta ante 429, 5xx o fallo de conexión.
        'retry_times' => 3,
        // Espera antes del reintento N: N × este valor (500 ms, 1000 ms…).
        'retry_sleep_ms' => 500,
    ],

    // Proveedor de cada categoría (CatalogProviderRegistry), en app/Catalog/Providers (F1-03).
    'categories' => [
        'peliculas' => ['provider' => \App\Catalog\Providers\TmdbMovieProvider::class],
        'series' => ['provider' => \App\Catalog\Providers\TmdbTvProvider::class],
        // F4: 'videojuegos' => [...], 'destinos' => [...]
    ],

    'providers' => [
        // Token v4 (Bearer); lo da de alta el humano (F1-00). Límite propio por debajo de los ~40/s de TMDB.
        'tmdb' => [
            'token' => env('TMDB_API_TOKEN') ?: null,
            'rate_per_second' => 20,
            // Días que se cachean las listas de géneros en español (/genre/{movie|tv}/list).
            'genres_cache_days' => 7,
        ],
        // Wikidata y Wikipedia comparten límite (200/min con User-Agent en Wikipedia).
        'wikimedia' => [
            'rate_per_minute' => 150,
            // Una consulta SPARQL puede tardar hasta 60 s en el servidor de Wikidata.
            'sparql_timeout_seconds' => 60,
            // Días que el DescriptionResolver cachea las respuestas de Wikidata (por QID) y de Wikipedia (por título).
            'cache_days' => 7,
        ],
    ],

    // Importación bajo demanda desde /buscar (F1-07, pipeline-catalogo §7). La petición del usuario
    // espera a la fuente externa, así que el timeout es corto y no hay reintentos.
    'on_demand' => [
        // Peticiones por minuto y usuario (RateLimiter 'catalog-search' y 'catalog-import', AppServiceProvider).
        'search_per_minute' => 10,
        'import_per_minute' => 5,
        // Minutos que se cachea cada búsqueda por (categoría, texto).
        'search_cache_minutes' => 10,
        // Sustituyen a http.timeout_seconds y http.retry_times durante la petición (1 = un solo intento).
        'timeout_seconds' => 5,
        'retry_times' => 1,
        // Segundos que se espera a otra importación simultánea del mismo título antes de responder 503.
        'lock_wait_seconds' => 10,
    ],

    // F2-06: 'queue_weights'; F4-01: 'genre_translations'
];
