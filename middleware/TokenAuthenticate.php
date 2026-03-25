<?php

namespace Golem15\Apparatus\Middleware;

use Backend\Facades\BackendAuth;
use Closure;
use Golem15\Apparatus\Models\PersonalApiToken;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class TokenAuthenticate
{
    public function handle(Request $request, Closure $next): Response
    {
        $plainToken = $request->bearerToken();

        if (!$plainToken) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        $token = PersonalApiToken::findByToken($plainToken);

        if (!$token) {
            return response()->json(['error' => 'Invalid token'], 401);
        }

        if ($token->isExpired()) {
            return response()->json(['error' => 'Token has expired'], 401);
        }

        $user = $token->user;

        if (!$user) {
            return response()->json(['error' => 'Token owner not found'], 401);
        }

        BackendAuth::setUser($user);
        $token->markAsUsed();

        return $next($request);
    }
}
