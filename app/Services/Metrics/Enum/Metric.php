<?php declare(strict_types = 1);

namespace App\Services\Metrics\Enum;

enum Metric: string
{
    case BOT_COMMAND_PROCESSING_TIME = 'bot_command_processing_time';
    case RPC_RESPONSE_TIME = 'rpc_response_time';
    case COMMAND_CONTEXT_PROCESSING_TIME = 'cmd_ctx_processing_time';
    case AMBIENT_TEMPERATURE = 'ambient_temperature';
    case AMBIENT_HUMIDITY = 'ambient_humidity';
    case JOB_EXECUTION_TIME = 'job_execution_time';
}
