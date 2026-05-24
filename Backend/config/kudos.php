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

    'rules' => [
        'daily_login_streak_cap' => 5,
    ],

    'reasons' => [
        'vote_first_time_item' => 'vote_first_time_item',
        'proposal_accepted' => 'proposal_accepted',
        'daily_login_streak' => 'daily_login_streak',
    ],
];

