<?php

return [
    'questions_per_session' => (int) env('PRACTICE_QUESTIONS_PER_SESSION', 10),
    'registration_enabled' => filter_var(env('REGISTRATION_ENABLED', true), FILTER_VALIDATE_BOOLEAN),
    'registration_requires_approval' => filter_var(env('REGISTRATION_REQUIRES_APPROVAL', false), FILTER_VALIDATE_BOOLEAN),
    'pagination' => [
        'questions' => (int) env('PAGINATION_QUESTIONS', 20),
        'users' => (int) env('PAGINATION_USERS', 20),
        'wrong_questions' => (int) env('PAGINATION_WRONG_QUESTIONS', 20),
        'attempts' => (int) env('PAGINATION_ATTEMPTS', 20),
    ],
];
