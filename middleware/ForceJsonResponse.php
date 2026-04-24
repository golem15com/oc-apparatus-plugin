<?php

namespace Golem15\Apparatus\Middleware;

use Closure;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Forces JSON responses for API routes regardless of what the client sends
 * as Accept header. Also catches any uncaught exceptions and returns JSON.
 */
class ForceJsonResponse
{
    public function handle(Request $request, Closure $next): Response
    {
        $request->headers->set('Accept', 'application/json');

        try {
            $response = $next($request);
        } catch (\Throwable $e) {
            $status = method_exists($e, 'getStatusCode') ? $e->getStatusCode() : 500;

            $data = ['error' => $e->getMessage()];

            if ($e instanceof \Illuminate\Validation\ValidationException) {
                $status = 422;
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
