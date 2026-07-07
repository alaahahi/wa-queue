<?php

return [
    'send_url' => env('TEXTMEBOT_SEND_URL', 'https://api.textmebot.com/send.php'),
    'status_url' => env('TEXTMEBOT_STATUS_URL', 'https://api.textmebot.com/status.php'),
    'api_key_rotation_days' => (int) env('TEXTMEBOT_KEY_ROTATION_DAYS', 7),
];
