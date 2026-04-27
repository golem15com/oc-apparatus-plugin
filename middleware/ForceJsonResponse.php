<?php

namespace Golem15\Apparatus\Middleware;

use Closure;
use Golem15\Apparatus\Classes\Traits\SafeExceptionResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Forces JSON responses for API routes regardless of what the client sends
 * as Accept header. Also catches any uncaught exceptions and returns JSON.
 *
 * Uses SafeExceptionResponse trait to sanitize exception messages:
 * - Known safe types (HttpException, ValidationException, AuthenticationException) pass through
 * - Unexpected exceptions return "Internal server error" when app.debug=false
 */
class ForceJsonResponse
{
    use SafeExceptionResponse;

    public function handle(Request $request, Closure $next): Response
    {
        $request->headers->set('Accept', 'application/json');

        try {
            $response = $next($request);
        } catch (\Throwable $e) {
            $status = $this->safeExceptionStatus($e);
            $message = $this->safeExceptionMessage($e);

            $data = ['error' => $message];

            if ($e instanceof \Illuminate\Validation\ValidationException) {
                $data['errors'] = $e->errors();
            }

            if (config('app.debug')) {
                $data['exception'] = get_class($e);
                $data['file'] = $e->getFile();
                $data['line'] = $e->getLine();
            }

            return new JsonResponse($data, $status);
        }

        return $response;
    }
}
