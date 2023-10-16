<?php

return [
    'gmail' => [
        'sender' => [
            'name' => 'Your Name',
            'email' => 'name@example.com',
        ],
    ],
    'calendar' => [
        'calendar_id' => env('GOOGLE_CALENDAR_ID'),
        'owner' => [
            'name' => 'Your Name',
            'email' => 'name@example.com',
        ],
    ],
];
