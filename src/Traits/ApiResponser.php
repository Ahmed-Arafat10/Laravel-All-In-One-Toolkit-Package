<?php

namespace AhmedArafat\AllInOne\Traits;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;

/**
 * Trait ApiResponser
 *
 * Provides a standardized and stable structure for API responses.
 *
 * IMPORTANT DESIGN RULE:
 * ---------------------
 * - This trait DOES NOT return HTTP responses.
 * - It ONLY builds response payloads (arrays).
 * - JsonApiResponser trait is responsible for returning response()->json().
 */
trait ApiResponser
{
    /**
     * Build a successful API response payload.
     *
     * This method represents the canonical "success" response shape
     * across the entire application.
     *
     * @param mixed $data The main response payload (models, arrays, primitives, etc.)
     * @param string|null $message Optional human-readable message
     * @param array $meta Optional metadata (pagination, flags, versioning, etc.)
     *
     * @return array{
     *     success: bool,
     *     message: string|null,
     *     data: mixed,
     *     errors: null,
     *     meta: array
     * }
     */
    protected function apiSuccess(
        mixed   $data = null,
        ?string $message = null,
        array   $meta = []
    ): array
    {
        return [
            'success' => true,
            'message' => $message,
            'data' => $data,
            'errors' => null,
            'meta' => $meta,
        ];
    }

    /**
     * Build a failed API response payload.
     *
     * This method is used for:
     * - Validation errors
     * - Business rule violations
     * - Domain-level failures
     *
     * It intentionally separates:
     * - A human-readable message
     * - A machine-readable errors array
     *
     * @param string $message High-level error message
     * @param array $errors Detailed error data (field => messages, codes, etc.)
     * @param array $meta Optional metadata (trace_id, error_code, etc.)
     *
     * @return array{
     *     success: bool,
     *     message: string,
     *     data: null,
     *     errors: array,
     *     meta: array
     * }
     */
    protected function apiError(
        string $message,
        array  $errors = [],
        array  $meta = []
    ): array
    {
        return [
            'success' => false,
            'message' => $message,
            'data' => null,
            'errors' => $errors,
            'meta' => $meta,
        ];
    }

    /**
     * Build a paginated API response payload.
     *
     * This method standardizes pagination output and prevents
     * leaking internal paginator structures to the client.
     *
     * It extracts:
     * - Items
     * - Pagination metadata
     *
     * @param LengthAwarePaginator $paginator Laravel paginator instance
     * @param string|null $message Optional response message
     *
     * @return array{
     *     success: bool,
     *     message: string|null,
     *     data: array,
     *     errors: null,
     *     meta: array{
     *         pagination: array{
     *             total: int,
     *             per_page: int,
     *             current_page: int,
     *             last_page: int
     *         }
     *     }
     * }
     */
    protected function apiPaginated(
        LengthAwarePaginator $paginator,
        ?string              $message = null
    ): array
    {
        return $this->apiSuccess(
            $paginator->items(),
            $message,
            [
                'pagination' => [
                    'total' => $paginator->total(),
                    'per_page' => $paginator->perPage(),
                    'current_page' => $paginator->currentPage(),
                    'last_page' => $paginator->lastPage(),
                ],
            ]
        );
    }

    /**
     * Build a response that contains only a message.
     *
     * Useful for:
     * - Delete operations
     * - State changes
     * - Acknowledgements
     *
     * @param string $message
     *
     * @return array{
     *     success: bool,
     *     message: string,
     *     data: null,
     *     errors: null,
     *     meta: array
     * }
     */
    protected function apiMessage(string $message): array
    {
        return $this->apiSuccess(null, $message);
    }
}