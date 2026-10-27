<?php

return [
    'rewards' => [
        'vote_first_time_item' => 5,
        'proposal_accepted' => 1000,
	    'daily_login_streak' => [
		    1 => ['base' => 10,  'bonus_chance' => 50,  'bonus_multiplier' => 2],
		    2 => ['base' => 25,  'bonus_chance' => 45, 'bonus_multiplier' => 2],
		    3 => ['base' => 50,  'bonus_chance' => 40, 'bonus_multiplier' => 2],
		    4 => ['base' => 100, 'bonus_chance' => 35, 'bonus_multiplier' => 3],
		    5 => ['base' => 200, 'bonus_chance' => 30, 'bonus_multiplier' => 5],
	    ],
    ],

	'voting' => [
		'reward' => 5,
		'diminishing_returns' => [
			'threshold' => 20,
			'new_reward' => 2,
		],
		'daily_cap' => 50,
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

