<?php

declare(strict_types=1);

namespace AhmedArafat\AllInOne\Traits;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

trait QueryParameter
{
    /**
     * Validate and resolve a single query parameter into a model instance.
     *
     * @param Request $request
     * @param string $keyName
     * @param class-string<Model> $model
     * @param string $column
     *
     * @return array{model: Model, value: int}|null
     */
    public function resolveQueryModel(
        Request $request,
        string  $keyName,
        string  $model,
        string  $column = 'id'
    ): ?array
    {
        $value = $request->query($keyName);
        if ($value === null) {
            return null;
        }
        if (!ctype_digit((string)$value)) {
            throw new BadRequestHttpException(
                "Value of key `$keyName` must be a numeric value."
            );
        }

        /** @var Model|null $modelInstance */
        $modelInstance = $model::query()
            ->where($column, (int)$value)
            ->first();

        if ($modelInstance === null) {
            throw new BadRequestHttpException(
                "Invalid value for key `$keyName`."
            );
        }
        return [
            'model' => $modelInstance,
            'value' => (int)$value,
        ];
    }

    /**
     * Validate and resolve a multi-select query parameter.
     *
     * Example:
     * ?ids=1,2,3
     *
     * @param Request $request
     * @param string $keyName
     * @param class-string<Model> $model
     * @param string $column
     *
     * @return int[]|null
     */
    public function resolveQueryModels(
        Request $request,
        string  $keyName,
        string  $model,
        string  $column = 'id'
    ): ?array
    {
        $value = $request->query($keyName);

        if ($value === null) {
            return null;
        }

        $rawIds = array_filter(
            explode(',', (string)$value),
            static fn($v) => $v !== ''
        );

        if (empty($rawIds)) {
            throw new BadRequestHttpException(
                "Value of key `$keyName` cannot be empty."
            );
        }

        foreach ($rawIds as $id) {
            if (!ctype_digit($id)) {
                throw new BadRequestHttpException(
                    "Values of key `$keyName` must be numeric."
                );
            }
        }

        $ids = array_unique(array_map('intval', $rawIds));

        $existingIds = $model::query()
            ->whereIn($column, $ids)
            ->pluck($column)
            ->all();

        $missingIds = array_diff($ids, $existingIds);

        if (!empty($missingIds)) {
            throw new BadRequestHttpException(
                "Invalid values for key `$keyName`: " . implode(', ', $missingIds)
            );
        }

        return $ids;
    }
}
