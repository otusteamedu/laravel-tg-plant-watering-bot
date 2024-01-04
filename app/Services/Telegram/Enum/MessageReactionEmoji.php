<?php declare(strict_types=1);

namespace App\Services\Telegram\Enum;

enum MessageReactionEmoji: string
{
    case THUMB_UP = '👍';
    case THUMB_DOWN = '👎';
    case MOON_DARK = '🌚';
    case EYES = '👀';
}
