<?php

return [
    'mqtt' => [
        'topic_in' => env('PLANT_RPC_TOPIC_IN', 'plant/rpc/in'),
        'topic_out' => env('PLANT_RPC_TOPIC_OUT', 'plant/rpc/out'),
        'topic_events' => env('PLANT_RPC_TOPIC_EVENTS', 'plant/events'),
    ],
    'rpc_max_attempts' => 3,
];
