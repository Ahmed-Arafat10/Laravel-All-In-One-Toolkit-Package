<?php

namespace AhmedArafat\AllInOne\Helpers;

use AhmedArafat\AllInOne\Exceptions\ExceptionWithJsonApiResponser;
use AhmedArafat\AllInOne\Exceptions\ValidationErrorsAsArrayException;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Session\TokenMismatchException;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Throwable;

/**
 * Class ExceptionHelper
 *
 * Centralized API Exception Handler for All-In-One Laravel Package.
 *
 * This helper converts thrown exceptions into standardized JSON API responses.
 * It ensures:
 *
 * - Consistent error structure
 * - Proper HTTP status codes
 * - No sensitive data leakage in production
 * - Extended debug information in local environment
 *
 * Registered via:
 * bootstrap/app.php → withExceptions()
 */
class ExceptionHelper
{
    /**
     * Register API exception rendering logic.
     *
     * This method hooks into Laravel 11 exception configuration
     * and transforms supported exceptions into JSON responses
     * using ExceptionWithJsonApiResponser.
     *
     * Handled Exceptions:
     *
     * - ModelNotFoundException → 404 Not Found
     * - ValidationException → 422 Unprocessable Entity
     * - ValidationErrorsAsArrayException → 422 Unprocessable Entity
     * - AuthenticationException → 401 Unauthorized
     * - AuthorizationException → 403 Forbidden
     * - NotFoundHttpException → 404 Not Found
     * - MethodNotAllowedHttpException → 405 Method Not Allowed
     * - TokenMismatchException → 419 Authentication Timeout
     * - QueryException → 500 Internal Server Error
     * - HttpException → Dynamic status code
     * - Fallback → 500 Internal Server Error
     *
     * Production Behavior:
     * - Database details are hidden.
     *
     * Local Behavior:
     * - SQL and error codes are included inside meta.
     *
     * @param Exceptions $exceptions Laravel exception configuration instance
     * @return void
     */
    public static function apiExceptionThrower(Exceptions &$exceptions): void
    {
        $exceptions->render(function (Throwable $e) {

            if (!self::shouldReturnJson()) {
                return null;
            }

            $api = new ExceptionWithJsonApiResponser();

            /**
             * 404 Not Found
             * Thrown when an Eloquent model cannot be found.
             */
            if ($e instanceof ModelNotFoundException) {
                return $api->jsonError(
                    app()->isProduction()
                        ? 'Resource not found.'
                        : $e->getMessage(),
                    Response::HTTP_NOT_FOUND
                );
            }

            /**
             * 422 Unprocessable Entity
             * Thrown when request validation fails.
             */
            if ($e instanceof ValidationException) {
                return $api->jsonError(
                    'Validation failed.',
                    Response::HTTP_UNPROCESSABLE_ENTITY,
                    $e->errors()
                );
            }

            /**
             * 422 Unprocessable Entity
             * Custom validation exception with pre-formatted array errors.
             */
            if ($e instanceof ValidationErrorsAsArrayException) {
                return $api->jsonError(
                    'Validation failed.',
                    Response::HTTP_UNPROCESSABLE_ENTITY,
                    json_decode($e->getMessage(), true) ?? []
                );
            }

            /**
             * 401 Unauthorized
             * Thrown when user is not authenticated.
             */
            if ($e instanceof AuthenticationException) {
                return $api->jsonError(
                    $e->getMessage(),
                    Response::HTTP_UNAUTHORIZED
                );
            }

            /**
             * 403 Forbidden
             * Thrown when authenticated user lacks permission.
             */
            if ($e instanceof AuthorizationException) {
                return $api->jsonError(
                    $e->getMessage(),
                    Response::HTTP_FORBIDDEN
                );
            }

            /**
             * 404 Not Found
             * Thrown when API endpoint does not exist.
             */
            if ($e instanceof NotFoundHttpException) {
                return $api->jsonError(
                    $e->getMessage(),
                    Response::HTTP_NOT_FOUND
                );
            }

            /**
             * 405 Method Not Allowed
             * Thrown when HTTP method is not allowed on route.
             */
            if ($e instanceof MethodNotAllowedHttpException) {
                return $api->jsonError(
                    $e->getMessage(),
                    Response::HTTP_METHOD_NOT_ALLOWED
                );
            }

            /**
             * 419 Authentication Timeout
             * Thrown when CSRF token is invalid or expired.
             */
            if ($e instanceof TokenMismatchException) {
                return $api->jsonError(
                    $e->getMessage(),
                    419
                );
            }

            /**
             * 500 Internal Server Error
             * Thrown for database-related failures.
             *
             * In production:
             * - Generic error message returned.
             *
             * In local:
             * - SQL query and DB error code included in meta.
             */
            if ($e instanceof QueryException) {

                if (app()->isProduction()) {
                    return $api->jsonError(
                        'Database error occurred.',
                        Response::HTTP_INTERNAL_SERVER_ERROR
                    );
                }

                return $api->jsonError(
                    $e->getMessage(),
                    Response::HTTP_INTERNAL_SERVER_ERROR,
                    [],
                    [
                        'sql'  => $e->getSql(),
                        'code' => $e->errorInfo[1] ?? null,
                    ]
                );
            }

            /**
             * Dynamic HTTP Status
             * Covers Symfony HttpException (400, 403, 500, etc.)
             */
            if ($e instanceof HttpException) {
                return $api->jsonError(
                    $e->getMessage(),
                    $e->getStatusCode()
                );
            }

            /**
             * 500 Internal Server Error (Fallback)
             * Handles any unhandled Throwable.
             */
            $status = ($e->getCode() >= 400 && $e->getCode() < 600)
                ? $e->getCode()
                : Response::HTTP_INTERNAL_SERVER_ERROR;

            return $api->jsonError(
                $e->getMessage(),
                $status
            );
        });
    }

    /**
     * Determine whether the current request expects a JSON response.
     *
     * Conditions:
     * - Route URI starts with "api/*"
     * - OR request explicitly expects JSON
     *
     * @return bool
     */
    protected static function shouldReturnJson(): bool
    {
        return request()->is('api/*') || request()->expectsJson();
    }
}
