<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;

class LoggingService
{
    public function logErrorEmail(string $message, array $context = []): void
    {
        Log::channel('email-service')->error($message, $this->formatContext($context));
    }

    public function logInfoEmail(string $message, array $context = []): void
    {
        Log::channel('email-service')->info($message, $this->formatContext($context));
    }

    private function formatContext(array $context): array 
    {
        return array_merge($context, [
            'timestamp' => now()->format('Y-m-d H:i:s.u'),
            'memory' => memory_get_usage(true),
            'pid' => getmypid()
        ]);
    }
}
