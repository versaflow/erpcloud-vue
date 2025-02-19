<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Support\Facades\Log;

class LogWebsocketEvents
{
    public function handle($request, Closure $next)
    {
        if ($request->is('broadcasting/*')) {
            Log::info('Websocket auth attempt', [
                'channel' => $request->input('channel_name'),
                'socket_id' => $request->input('socket_id')
            ]);
        }

        $response = $next($request);

        if ($request->is('broadcasting/*')) {
            Log::info('Websocket auth response', [
                'status' => $response->status(),
                'content' => $response->content()
            ]);
        }

        return $response;
    }
}
