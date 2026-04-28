<?php namespace Golem15\Apparatus\Classes\Traits;

use Illuminate\Auth\AuthenticationException;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\HttpException;

/**
 * Shared exception sanitization trait.
 *
 * Provides two methods for callers to build their own JSON response shape:
 *   - safeExceptionMessage(): returns sanitized message string
 *   - safeExceptionStatus(): returns appropriate HTTP status code
 *
 * Known safe exception types (ValidationException, HttpException,
 * AuthenticationException) pass through their messages. Unexpected exceptions
 * return "Internal server error" in production and the real message in debug mode.
 *
 * @package Golem15\Apparatus\Classes\Traits
 */
trait SafeExceptionResponse
{
    /**
     * Get a safe exception message, sanitizing unexpected exceptions in production.
     */
    protected function safeExceptionMessage(\Throwable $e): string
    {
        // Known safe exception types -- pass through their messages
        if ($e instanceof ValidationException
            || $e instanceof HttpException
            || $e instanceof AuthenticationException) {
            return $e->getMessage();
        }

        // Unexpected exceptions -- show full message only in debug mode
        if (config('app.debug')) {
            return $e->getMessage();
        }

        // Always log the real exception server-side
        \Log::error($e->getMessage(), ['exception' => $e]);

        return 'Internal server error';
    }

    /**
     * Get the appropriate HTTP status code for the exception.
     */
    protected function safeExceptionStatus(\Throwable $e): int
    {
        if ($e instanceof ValidationException) {
            return 422;
        }
        if ($e instanceof HttpException) {
            return $e->getStatusCode();
        }
        if ($e instanceof AuthenticationException) {
            return 401;
        }

        return method_exists($e, 'getStatusCode') ? $e->getStatusCode() : 500;
    }
}
