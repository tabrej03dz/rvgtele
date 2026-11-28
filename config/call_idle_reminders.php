<?php

return [
    'enabled' => env('CALL_IDLE_REMINDERS_ENABLED', false),
    'start' => env('CALL_IDLE_WORK_START', '10:00'),
    'end' => env('CALL_IDLE_WORK_END', '19:00'),
    'openai_key' => env('OPENAI_API_KEY'),
    'openai_model' => env('OPENAI_PURCHASE_BILL_MODEL', 'gpt-4o-mini'),
];