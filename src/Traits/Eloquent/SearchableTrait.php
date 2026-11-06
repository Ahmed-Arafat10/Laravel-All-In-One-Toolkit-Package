<?php

namespace AhmedArafat\AllInOne\Traits\Eloquent;

use Illuminate\Database\Eloquent\Builder;
use function AhmedArafat\AllInOne\Traits\request;

/**
 * Search configured columns on the model and its relationships using the search query parameter.
 */
trait SearchableTrait
{
    /**
     * Add LIKE predicates for each word and column and order by match count.
     *
     * @param Builder $query
     * @param string[] $columnsArray
     * @param string $table
     * @param string $search
     * @return void
     */
    private function searchBuilder(Builder &$query, $columnsArray, string $table, string $search): void
    {
        $words = explode(' ', $search);
        foreach ($columnsArray as $col) {
            $tableColName = $table . '.' . $col;
            foreach ($words as $word) {
                $query->orWhere($tableColName, 'LIKE', "%$word%");
            }
        }
        $binding = [];
        $matchCountExpression = $this->matchCountExpressionBuilder($columnsArray, $table, $words, $binding);
        $query->selectRaw('*, (' . $matchCountExpression . ') as match_count')
            ->orderByDesc('match_count');
    }

    /**
     * Build a SQL expression that counts matching word and column pairs.
     *
     * @param array $columnsArray
     * @param string $table
     * @param array $words
     * @param array $binding
     * @return string
     */
    private function matchCountExpressionBuilder(array &$columnsArray, string &$table, array &$words, array $binding): string
    {
        return implode(' + ', array_map(function ($col) use (&$table, &$words, &$binding) {
            return implode(' + ', array_map(function ($word) use (&$table, &$col, &$binding) {
                $binding[] = "%$word%";
                return "CASE WHEN $table.$col LIKE ? THEN 1 ELSE 0 END"; // . PHP_EOL
            }, $words));
        }, $columnsArray));
    }

    /**
     * Group keyword predicates for columns on the current model.
     *
     * @param Builder $q
     * @param array $columns
     * @param string $search
     * @param string $relation
     * @param int $countIterations
     * @return void
     */
    public function searchInSameRelation(Builder &$q, array &$columns, $search, $relation, &$countIterations): void
    {
        $relationshipMethodName = !$countIterations ? 'where' : 'orWhere';
        $q->{$relationshipMethodName}(function ($q) use (&$columns, &$search, &$relation) {
            $this->searchBuilder($q, $columns, $relation, $search);
        });
    }

    /**
     * Group keyword predicates within a relationship existence query.
     *
     * @param Builder $q
     * @param array $columns
     * @param string $search
     * @param string $relation
     * @param string $methodName
     * @param int $countIterations
     * @return void
     */
    public function searchInAnotherRelation(Builder &$q, array &$columns, $search, $relation, $methodName, &$countIterations): void
    {
        $relationshipMethodName = !$countIterations ? 'whereHas' : 'orWhereHas';
        $q->{$relationshipMethodName}($methodName, function ($q2) use (&$search, &$columns, &$relation) {
            $q2->where(function ($q3) use (&$search, &$columns, &$relation) {
                $this->searchBuilder($q3, $columns, $relation, $search);
            });
        });
    }

    /**
     * Apply the request's search terms to the model's configured searchableColumns.
     *
     * @param Builder $q
     * @return Builder
     */
    public function scopeSearch(Builder $q): Builder
    {
        $search = request()->query('search');
        if (empty($search) || !isset($this->searchableColumns)) return $q;
        $countIterations = 0;
        foreach ($this->searchableColumns as $relation => $keys) {
            $relation = ltrim($relation, '-'); // if a table name exists in the array, add another key with - at the beginning
            if ($relation == $this->getTable()) $this->searchInSameRelation($q, $keys['columns'], $search, $relation, $countIterations);
            else $this->searchInAnotherRelation($q, $keys['columns'], $search, $relation, $keys['methodName'], $countIterations);
            $countIterations++;
        }
        //$q->dd();
        return $q;
    }
}
