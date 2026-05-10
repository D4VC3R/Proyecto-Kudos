<?php

return [
    'rewards' => [
        'vote_first_time_item' => 10,
        'proposal_accepted' => 1000,
        'daily_login_streak' => [
            1 => 10,
            2 => 25,
            3 => 50,
            4 => 100,
            5 => 200,
        ],
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

