<?php

namespace AhmedArafat\AllInOne\Traits;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\JsonResponse;

/**
 * Trait JsonApiResponser
 *
 * Adds HTTP (JSON) response capabilities
 * on top of ApiResponser payload builders.
 *
 * IMPORTANT:
 * - Depends on Laravel HTTP layer
 * - Should only be used in controllers / middleware
 */
trait JsonApiResponser
{
    use ApiResponser;

    /**
     * Return a successful JSON r esponse.
     */
    public function jsonSuccess(
        mixed   $data = null,
        ?string $message = null,
        int     $status = 200,
        array   $meta = []
    ): JsonResponse
    {
        return response()->json(
            $this->apiSuccess($data, $message, $meta),
            $status
        );
    }

    /**
     * Return a failed JSON response.
     */
    public function jsonError(
        string $message,
        int    $status = 400,
        array  $errors = [],
        array  $meta = []
    ): JsonResponse
    {
        return response()->json(
            $this->apiError($message, $errors, $meta),
            $status
        );
    }

    /**
     * Return a standardized paginated JSON response.
     *
     * This method:
     * - Uses ApiResponser to build a stable pagination payload
     * - Ensures pagination metadata is always placed in `meta`
     * - Prevents leaking Laravel paginator internals
     *
     * @param LengthAwarePaginator $paginator
     * @param string|null $message
     * @param int $status
     *
     * @return JsonResponse
     */
    protected function jsonPaginated(
        LengthAwarePaginator $paginator,
        ?string              $message = null,
        int                  $status = 200
    ): JsonResponse
    {
        return response()->json(
            $this->apiPaginated($paginator, $message),
            $status
        );
    }

    /**
     * Return a JSON response with only a message.
     *
     * Useful for:
     * - Delete operations
     * - State transitions
     * - Acknowledgements (e.g. "Email sent")
     *
     * @param string $message
     * @param int $status HTTP status code (default: 200 OK)
     *
     * @return JsonResponse
     */
    public function jsonMessage(
        string $message,
        int    $status = 200
    ): JsonResponse
    {
        return response()->json(
            $this->apiMessage($message),
            $status
        );
    }
}
