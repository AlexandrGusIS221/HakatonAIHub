<?php
// app/config/config.php
declare(strict_types=1);

return [
    'app' => [
        'name' => 'Task Manager',
        'version' => '1.0.0'
    ],
    'points' => [
        'level_multiplier' => 100,
        'achievements' => [
            'first_task' => 50,
            'perfect_week' => 200,
            'task_master' => 500
        ]
    ]
];