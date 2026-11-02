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
        'buscar', 'ranking', 'admin', 'profile', 'my-proposals', 'forbidden', '404', 'onboarding',
        'login', 'register', 'verify-email', 'forgot-password', 'password-reset', 'privacidad', 'aviso-legal', 'mis-listas', 'u',
        // Rutas previstas (docs/frontend/arquitectura.md §2)
        'wishlist', 'listas',
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
        'videojuegos' => ['provider' => \App\Catalog\Providers\IgdbGameProvider::class],
        // F4-02: 'destinos' => [...]
    ],

    'providers' => [
        // Token v4 (Bearer); lo da de alta el humano (F1-00). Límite propio por debajo de los ~40/s de TMDB.
        'tmdb' => [
            'token' => env('TMDB_API_TOKEN') ?: null,
            'rate_per_second' => 20,
            // Días que se cachean las listas de géneros en español (/genre/{movie|tv}/list).
            'genres_cache_days' => 7,
        ],
        // IGDB (F4-01): token de aplicación de Twitch (client_credentials); lo da de alta el humano.
        // El límite publicado es 4 peticiones/s; la ventana del RateLimiter es fija, así que se usa 3 para no pasarse.
        'igdb' => [
            'client_id' => env('IGDB_CLIENT_ID') ?: null,
            'client_secret' => env('IGDB_CLIENT_SECRET') ?: null,
            'rate_per_second' => 3,
        ],
        // Steam Store appdetails (F4-01, sin clave): solo la descripción corta. Límite conocido: ~200 cada 5 min
        // por IP; 1 petición cada 2 s (150 cada 5 min) para quedar por debajo.
        'steam' => [
            'max_requests' => 1,
            'per_seconds' => 2,
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

    // Cola de votación ponderada (F2-06, NextCategoryItemService::generateNewBatch()).
    // Peso de cada candidato: pop·popularidad normalizada (escala logarítmica, 0–1, respecto a la más alta
    // de los candidatos) + new·recencia (0–1) + qual·quality_score/100 + rand·azar (0–1). Suman 1.
    'queue_weights' => [
        'pop' => 0.45,
        'new' => 0.2,
        'qual' => 0.15,
        'rand' => 0.2,
    ],

    // Candidatos que se puntúan antes de quedarse con los 50 de más peso, en una sola consulta (UNION):
    // los más populares más unos cuantos al azar, para que la cola no se limite al top.
    'queue_candidates' => [
        'popular' => 300,
        'random' => 100,
    ],

    // Recencia: 1 si el lanzamiento es de los últimos full_days días o futuro; baja en línea recta
    // hasta 0 a los zero_years años. Sin released_at vale 0.
    'queue_recency' => [
        'full_days' => 90,
        'zero_years' => 5,
    ],

    // Onboarding (F2-07, GET /api/onboarding/items): número máximo de ítems del speed-rating, repartidos
    // por turnos entre las categorías elegidas (los más populares de cada una, activos, descubribles y sin voto del usuario).
    'onboarding_items' => 10,

    // "Similares" de la ficha (F2-08, SimilarItemsService). Puntuación de un candidato de la misma categoría:
    // genre por cada género en común + people si comparte dirección o creación + decade si es de la misma década.
    // Solo cuentan los candidatos con puntuación > 0; desempate por bayes_score y después por popularity.
    'similar' => [
        // Ítems que devuelve GET /api/items/{id}/similar.
        'limit' => 12,
        // Horas que se cachea la lista de similares de cada ítem.
        'cache_hours' => 24,
        // IDs que se guardan en caché por ítem: más que 'limit' para que las recomendaciones puedan
        // descartar los ya votados por el usuario y los que hayan dejado de ser elegibles.
        'cache_size' => 60,
        'weights' => [
            'genre' => 1,
            'people' => 2,
            'decade' => 1,
        ],
    ],

    // "Porque te gustó X" (F2-08, GET /api/recommendations, RecommendationService).
    'recommendations' => [
        // Nota mínima (0–10) de una valoración para servir de punto de partida.
        'min_score' => 8,
        // Con menos valoraciones así se devuelven populares de sus intereses.
        'min_liked' => 3,
        // Últimas valoraciones altas que se usan (una fila por cada una).
        'seeds' => 5,
        // Ítems por fila.
        'items_per_group' => 12,
        // Ítems de la fila de populares (fallback).
        'popular_limit' => 12,
    ],

    // Portada (F2-09, GET /api/home, HomeFeedService). La parte anónima (populares, novedades, listas
    // editoriales y mejor valorados) se cachea como IDs; con sesión, "Para ti" sale de las recomendaciones.
    'home' => [
        // Minutos que se cachea la parte anónima. Se borra al editar una lista editorial.
        'cache_minutes' => 10,
        // Ítems de las filas de novedades y mejor valorados (los populares sin sesión usan recommendations.popular_limit).
        'section_limit' => 12,
        // Novedades: released_at entre hoy - past_days y hoy + future_days.
        'new_releases_past_days' => 60,
        'new_releases_future_days' => 30,
        // Listas editoriales públicas que salen en la portada y primeros ítems de cada una.
        'editorial_lists' => 4,
        'items_per_list' => 6,
        // Mejor valorados: votos mínimos.
        'top_rated_min_votes' => 3,
    ],

    // Géneros de IGDB en español (F4-01, IgdbGameProvider). IGDB solo los da en inglés; un género que no
    // esté aquí conserva su nombre original.
    'genre_translations' => [
        'igdb' => [
            'Adventure' => 'Aventura',
            'Arcade' => 'Arcade',
            'Card & Board Game' => 'Cartas y tablero',
            'Fighting' => 'Lucha',
            "Hack and slash/Beat 'em up" => "Hack and slash / Beat 'em up",
            'Indie' => 'Indie',
            'MOBA' => 'MOBA',
            'Music' => 'Música',
            'Pinball' => 'Pinball',
            'Platform' => 'Plataformas',
            'Point-and-click' => 'Aventura gráfica',
            'Puzzle' => 'Puzle',
            'Quiz/Trivia' => 'Preguntas y respuestas',
            'Racing' => 'Carreras',
            'Real Time Strategy (RTS)' => 'Estrategia en tiempo real',
            'Role-playing (RPG)' => 'Rol',
            'Shooter' => 'Disparos',
            'Simulator' => 'Simulación',
            'Sport' => 'Deportes',
            'Strategy' => 'Estrategia',
            'Tactical' => 'Táctica',
            'Turn-based strategy (TBS)' => 'Estrategia por turnos',
            'Visual Novel' => 'Novela visual',
        ],
    ],

    // Plataformas de IGDB en español (F4-01). Casi todas son nombres propios y se quedan como están.
    'platform_translations' => [
        'igdb' => [
            'PC (Microsoft Windows)' => 'PC',
            'Web browser' => 'Navegador web',
            'Arcade' => 'Recreativa',
            'Legacy Mobile Device' => 'Móvil antiguo',
        ],
    ],
];
