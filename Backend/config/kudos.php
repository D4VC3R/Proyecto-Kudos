<?php

return [
    'rewards' => [
        'proposal_accepted' => 1000,
	    'daily_login_streak' => [
		    1 => ['base' => 10,  'bonus_chance' => 50,  'bonus_multiplier' => 2],
		    2 => ['base' => 25,  'bonus_chance' => 45, 'bonus_multiplier' => 2],
		    3 => ['base' => 50,  'bonus_chance' => 40, 'bonus_multiplier' => 2],
		    4 => ['base' => 100, 'bonus_chance' => 35, 'bonus_multiplier' => 3],
		    5 => ['base' => 200, 'bonus_chance' => 30, 'bonus_multiplier' => 5],
	    ],
    ],

	// Recompensa por votar un ítem por primera vez (F2-03). Valores bajos a propósito: los kudos no deben
	// premiar el voto masivo, sino valorar lo que de verdad se conoce. Hasta 'threshold' votos al día se
	// da 'reward'; por encima, 'new_reward'; a partir del voto daily_cap + 1 el voto ya no da kudos.
	// Con threshold = daily_cap (20 y 20) el tramo de 'new_reward' no se alcanza: el voto 21 supera el tope.
	'voting' => [
		'reward' => 2,
		'diminishing_returns' => [
			'threshold' => 20,
			'new_reward' => 1,
		],
		'daily_cap' => 20,
	],

    // Ranking bayesiano (F0-04): bayes = (v·R + m·C) / (v + m) si v > 0; 0 si v = 0.
    // v = vote_count, R = vote_avg, C = media de los votos de la categoría (cacheada 1 h en category-mean:{id}).
    'ranking' => [
        // m: votos "virtuales" con la media de la categoría que se suman a cada ítem.
        // Cuanto mayor es, más votos necesita un ítem para separarse de la media de su categoría.
        'bayes_min_votes' => 5,
        // C que se usa cuando la categoría todavía no tiene ningún voto.
        'category_mean_fallback' => 5.0,
    ],

    // Métricas de uso (F2-11, comando kudos:metrics; producto/vision.md §4).
    'metrics' => [
        // Valoraciones (votos de tipo 'vote') que necesita un ítem para contar como "bien valorado".
        'min_ratings' => 5,
    ],

    // Reportes de contenido (F3-04, DSA): máximo de reportes por usuario y hora.
    'reports' => [
        'per_hour' => 10,
    ],

    // Listas de usuario (F3-01): máximo de listas vivas por usuario (sin contar editoriales ni borradas)
    // y de ítems por lista (este también vale para las listas editoriales).
    'lists' => [
        'max_per_user' => 50,
        'max_items' => 100,
    ],

    // Perfil público (F3-03, GET /api/users/{username}).
    // reserved_usernames: nombres que nadie puede elegir, además de config('catalog.reserved_slugs'), para no suplantar
    // a la plataforma ni al equipo. Se comparan sin distinguir mayúsculas.
    // top_per_category: ítems mejor valorados por categoría; lists_limit y reviews_limit: cuántas listas públicas y
    // reseñas recientes se muestran (las más recientes primero).
    'profiles' => [
        'reserved_usernames' => [
            'admin', 'administrador', 'administrator', 'api', 'kudos', 'root', 'soporte', 'support', 'moderador',
            'moderator', 'mod', 'staff', 'equipo', 'team', 'sistema', 'system', 'null', 'undefined', 'anonimo',
            'anonymous', 'user', 'users', 'usuario', 'usuarios', 'perfil', 'ajustes', 'settings', 'ayuda', 'help',
            'legal', 'contacto', 'contact', 'notificaciones', 'notifications', 'lists', 'reports', 'reportes',
        ],
        'top_per_category' => 5,
        'lists_limit' => 20,
        'reviews_limit' => 10,
    ],

    // Correo de contacto legal (aviso legal, DSA): es la vía de reclamación que reciben los autores
    // cuando se oculta o borra su contenido. Vacío = la notificación no incluye la vía de reclamación.
    'legal' => [
        'contact_email' => env('LEGAL_CONTACT_EMAIL') ?: null,
    ],

    // Aviso de estreno de la wishlist (F3-06, comando wishlist:notify-releases). Si falta la marca de la
    // última ejecución en la caché (primera ejecución o desalojo de Redis), se revisan los estrenos de los
    // últimos N días. No duplica avisos: antes de enviar comprueba si el usuario ya tiene el de ese ítem.
    'wishlist' => [
        'release_lookback_days' => 7,
    ],

    'rules' => [
        'daily_login_streak_cap' => 5,
    ],

    'reasons' => [
        'vote_first_time_item' => 'vote_first_time_item',
        'proposal_accepted' => 'proposal_accepted',
        'daily_login_streak' => 'daily_login_streak',
    ],

    // Credenciales del administrador que crea UserSeeder. Se leen aquí (y no con env() en el seeder)
    // porque con la configuración cacheada env() devuelve null fuera de config/.
    'seed_admin' => [
        'name' => env('SEED_ADMIN_NAME', 'kudosAdmin'),
        'email' => env('SEED_ADMIN_EMAIL'),
        'password' => env('SEED_ADMIN_PASSWORD'),
    ],
];

