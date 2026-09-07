<?php

namespace App\Support\Security;

use App\Enum\SecurityEvent;
use Illuminate\Support\Facades\Log;

class SecurityAuditor
{
    public static function log(SecurityEvent $event, array $context = []): void
    {
        $request = request();

        Log::channel('security')->info($event->value, array_filter(array_merge([
            'event' => $event->value,
            'id_usuario' => $request?->user()?->getAuthIdentifier(),
            'ip' => $request?->ip(),
            'user_agent' => $request?->userAgent(),
        ], $context), static fn ($value) => $value !== null));
    }
}
