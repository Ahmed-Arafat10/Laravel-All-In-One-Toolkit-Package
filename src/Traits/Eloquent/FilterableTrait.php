<?php

namespace AhmedArafat\AllInOne\Traits\Eloquent;

use Exception;
use Illuminate\Database\Eloquent\Builder;

/**
 * Filter model and related-table columns using the configured query parameter mappings.
 */
trait FilterableTrait
{
    /**
     * Add an equality condition on a table-qualified column.
     *
     * @param Builder $q
     * @param string $col
     * @param string $table
     * @param string $search
     * @return void
     */
    private function filterBuilder(Builder &$q, $col, string $table, string $search): void
    {
        $tableColName = $table . '.' . $col;
        $q->where($tableColName, $search);
    }

    /**
     * Apply the filter value directly to a column on the model's table.
     *
     * @param Builder $q
     * @param string $column
     * @param string $search
     * @param string $relation
     * @return void
     */
    public function filterInSameRelation(Builder &$q, &$column, $search, $relation): void
    {
        $this->filterBuilder($q, $column, $relation, $search);
    }

    /**
     * Require a related row whose mapped column equals the filter value.
     *
     * @param Builder $q
     * @param string $column
     * @param string $search
     * @param string $relation
     * @param string $methodName
     * @return void
     */
    public function filterInAnotherRelation(Builder &$q, &$column, $search, $relation, $methodName): void
    {
        $q->whereHas($methodName, function ($q2) use (&$search, &$column, &$relation) {
            $q2->where(function ($q3) use (&$search, &$column, &$relation) {
                $this->filterBuilder($q3, $column, $relation, $search);
            });
        });
    }

    /**
     * Split a comma-separated filter parameter and reject empty entries.
     *
     * @param string $url
     * @param string $keyName
     * @throws Exception When the list contains an empty entry.
     * @return list<string>
     */
    private function checkFormat($url, $keyName)
    {
        $arr = explode(',', $url);
        foreach ($arr as $value) {
            if ($value == '') throw new Exception("Format Of `$keyName` Is Wrong");
        }
        return $arr;
    }

    /**
     * Apply paired column and value filters from the request using the model's filterableColumns map.
     *
     * @param Builder $q
     * @param string $filterColKey
     * @param string $filterValKey
     * @return Builder
     * @throws Exception
     */
    public function scopeFilter(Builder $q, string $filterColKey = 'filter_col', string $filterValKey = 'filter_val'): Builder
    {
        if (!isset($this->filterableColumns) ||
            request()->query($filterColKey) == null
            || request()->query($filterValKey) == null) return $q;
        $filterColKey = request()->query($filterColKey);
        $filterValKey = request()->query($filterValKey);

        $filterColumns = $this->checkFormat($filterColKey, $filterColKey);
        $filterValues = $this->checkFormat($filterValKey, $filterValKey);
        if (count($filterColumns) != count($filterValues)) throw new Exception("Filter Columns Does Not Match With Filter Values");
        foreach ($filterColumns as $urlCol) {
            $freq = 0;
            foreach ($this->filterableColumns as $relation => $item) {
                foreach ($item['columns'] as $col => $value) {
                    if ($col == $urlCol) $freq++;
                }
            }
            if (!$freq) throw new Exception("Invalid Filter Key `$urlCol`");
        }
        foreach ($this->filterableColumns as $relation => $item) {
            foreach ($item['columns'] as $mappedColumn => $realColumn) {
                if (in_array($mappedColumn, $filterColumns)) {
                    $key = array_search($mappedColumn, $filterColumns);
                    $val = $filterValues[$key];
                    $q->where(function ($q) use (&$filterValKey, &$relation, &$item, &$realColumn, &$val) {
                        if ($relation == $this->getTable()) $this->filterInSameRelation($q, $realColumn, $val, $relation);
                        else $this->filterInAnotherRelation($q, $realColumn, $val, $relation, $item['methodName']);
                    });
                }
            }
        }
        //$q->dd();
        return $q;
    }
}
