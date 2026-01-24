<?php

namespace AhmedArafat\AllInOne\Traits;

use Illuminate\Http\Request;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

trait PaginatorHelper
{
    /**
     * Append sequential row numbers to paginated items.
     *
     * This method is useful for frontend tables that need a continuous
     * row index across paginated results (e.g. 1–10, 11–20, etc.).
     *
     * The row number is calculated based on the current page and
     * per-page value from the HTTP request.
     *
     * Example output attribute:
     * - page 1 → 1, 2, 3...
     * - page 2 → 11, 12, 13...
     *
     * ⚠️ Note:
     * - This method MUTATES the paginator's underlying collection.
     * - The attribute is added dynamically to each model instance.
     *
     * @param LengthAwarePaginator $paginator
     *        The paginator instance whose items will be modified.
     *
     * @param Request|null $request
     *        The current HTTP request. If null, the global request()
     *        helper will be used.
     *
     * @param string $perPageKey
     *        Query parameter name used to resolve the per-page value.
     *        Default: "per_page".
     *
     * @param string $pageKey
     *        Query parameter name used to resolve the current page.
     *        Default: "page".
     *
     * @param string $attribute
     *        The attribute name that will be appended to each item
     *        (e.g. "num", "index", "row").
     *
     * @param int $defaultPerPage
     *        Fallback per-page value if none is provided in the request.
     *
     * @return void
     */
    public function addRowNumbers(
        LengthAwarePaginator $paginator,
        ?Request             $request = null,
        string               $perPageKey = 'per_page',
        string               $pageKey = 'page',
        string               $attribute = 'num',
        int                  $defaultPerPage = 10
    ): void
    {
        $request ??= request();
        $perPage = (int)$request->query($perPageKey, $defaultPerPage);
        $page = (int)$request->query($pageKey, 1);
        $start = ($page - 1) * $perPage;
        $paginator->getCollection()->transform(function ($model) use (&$start, $attribute) {
            $model->{$attribute} = ++$start;
            return $model;
        });
    }
}
