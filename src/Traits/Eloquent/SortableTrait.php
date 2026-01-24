<?php

namespace AhmedArafat\AllInOne\Traits\Eloquent;

use Exception;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Query\Expression;
use Illuminate\Support\Facades\DB;
use function AhmedArafat\AllInOne\Traits\__;

trait SortableTrait
{

    /**
     * @param array $data
     * @param string $relationName
     * @return string
     */
    public function handelConcatRawOrderBy(array &$data, string $relationName): string
    {
        $res = $data['operation'] . '(';
        $sz = count($data['column_name']);
        for ($i = 0; $i < $sz; $i++) {
            $colName = $data['column_name'][$i];
            $res .= "`$relationName`.`$colName` ";
            if ($i != $sz - 1) {
                $res .= ' , ';
                $res .= " {$data['separator']} , ";
            }

        }
        $res .= ')';
        return $res;
    }

    /**
     * @param array $data
     * @param string $relationName
     * @return string
     */
    public function handleRawOrderBy(array &$data, string $relationName): string
    {
        if ($data['operation'] == 'CONCAT') return $this->handelConcatRawOrderBy($data, $relationName);
        return '';
    }

    /**
     * @param $target
     * @return array|int[]
     */
    public function validateColumns($target): array
    {
        foreach ($this->sortableColumns as $methodName => $item) {
            foreach ($item['cols'] as $key2 => $item2) {
                if ($target == $key2) {
                    $res = [
                        'exists' => 1,
                        'relation' => $item['relation'],
                        'methodName' => $methodName,
                        'is_raw' => false
                    ];
                    if (is_array($item2['column_name'])) {
                        $res['column_name'] = $this->handleRawOrderBy($item2, $item['relation']);
                        $res['is_raw'] = true;
                    } else
                        $res['column_name'] = $item2['column_name'];
                    return $res;
                }
            }
        }
        return [
            'exists' => 0
        ];
    }

    /**
     * @throws Exception
     */
    public function scopeSortByColumn(Builder $q, $target = null, $dir = null): Builder
    {
        if (!($target && $dir && isset($this->sortableColumns))) return $q;
        if (!in_array($dir, ['asc', 'desc'])) throw new Exception(__('Sort Direction Not Found'));
        $data = $this->validateColumns($target);
        if (!$data['exists']) throw new Exception(__('Column Not Found To Sort By'));
        if ($data['relation'] == $this->getTable()) {
            if (!$data['is_raw']) $q->orderBy($data['column_name'], $dir);
            else $q->orderByRaw($data['column_name'] . ' ' . $dir);
        } else {
            $methodDotColum = $data['methodName'] . '__' . $data['column_name'];
            $this->addOrderByColumnFromRelation($q, $methodDotColum);
            //$q->orderBy($methodDotColum, $dir);
            if (!$data['is_raw']) $q->orderBy($methodDotColum, $dir);
            else $q->orderByRaw($data['column_name'] . ' ' . $dir);
            //  $q->dd();
        }
        return $q;
    }

    /**
     * @param Builder $query
     * @param $relation_name
     * @param $operator
     * @param $type
     * @param $where
     * @return Builder
     */
    public function addOrderByColumnFromRelation(Builder $query, $relation_name, $operator = '=', $type = 'left', $where = false): Builder
    {
        $split = explode('__', $relation_name);
        $relation = $this->{$split[0]}();
        $related_column = $split[1];
        $related_table = $relation->getRelated()->getTable();
        $foreign_key = $relation->getQualifiedForeignKeyName();
        $parent_key = $relation->getQualifiedParentKeyName(); // $this->getTable() . '.' . $this->primaryKey
        if (empty($query->columns)) {
            $query->select($this->getTable() . '.*');
        }
        $query->leftJoin($related_table, $foreign_key, $operator, $parent_key, $type, $where);
        //method_exists($relation, 'getForeignKeyName')
        if ($relation instanceof HasMany) {
            $query->addSelect(new Expression("MAX(`$related_table`.`$related_column`) AS `{$split[0]}__$related_column`"));
            $columns = $this->getTableColumns($this->getTable());
            $query->groupBy(...$columns);
        } else if ($relation instanceof hasOne) {
            $query->addSelect(new Expression("`$related_table`.`$related_column` AS `{$split[0]}__$related_column`"));
        }
        return $query;
    }

    /**
     * @param $table
     * @return string[]
     */
    protected function getTableColumns($table): array
    {
        $columns = DB::getSchemaBuilder()->getColumnListing($this->getTable());
        return array_map(function ($column) use ($table) {
            return $table . '.' . $column;
        }, $columns);
    }
}
