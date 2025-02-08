<?php

return [
    'api' => [
        'code' => env('PRODUCTION_API_CODE', ''),
        'user_key' => env('PRODUCTION_USER_KEY', ''),
        'base_url' => env('PRODUCTION_API_URL', 'https://api-gw.dhlparcel.nl'),
    ]
]; 