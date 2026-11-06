<?php

namespace AhmedArafat\AllInOne\Traits;

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

/**
 * Define translation tables and file metadata columns in migrations.
 */
trait MigrationHelperTrait
{
    /**
     * Create a translations table with a cascading foreign key and one row per parent and locale.
     *
     * @param string $tableName
     * @param array $cols
     * @return void
     */
    public function createTranslatableRelation($tableName, $cols): void
    {
        $newTableNameSingular = Str::singular($tableName);
        $newTableName = $newTableNameSingular . '_translations';
        Schema::create($newTableName, function (Blueprint $table) use (&$newTableNameSingular, &$cols, &$newTableName, &$tableName) {
            $fk = $newTableNameSingular . '_id';
            $table->id();
            $table->foreignId($fk)
                ->constrained($tableName, indexName: $fk)
                ->cascadeOnDelete();
            $table->string('locale')->index();
            foreach ($cols as $col => $type) {
                if (is_array($type)) {
                    $resCol = $table->{$type['type']}($col);
                    if (isset($type['null']))  $resCol->nullable();
                }else $table->{$type}($col);
            }
            $table->unique([$fk, 'locale'], 'unique_' . $fk . '_locale');
        });
    }

    /**
     * Add prefixed stored and original filename columns with optional size and extension columns.
     *
     * @param Blueprint $table
     * @param string $prefix
     * @param bool $hasSize
     * @param bool $hasExtension
     * @param bool $isNullable
     * @return void
     */
    public static function fileColumn(Blueprint &$table, string $prefix, bool $hasSize = false, bool $hasExtension = false, $isNullable = false): void
    {
        $file = $table->string($prefix . '_file');
        $fileOrg = $table->string($prefix . '_file_org');
        if ($isNullable) {
            $file->nullable();
            $fileOrg->nullable();
        }
        if ($hasSize) $table->unsignedBigInteger($prefix . '_file_sz');
        if ($hasExtension) $table->unsignedBigInteger($prefix . '_file_ext')->nullable();
    }
    /**
     * Add standard file name, original name, size and extension columns.
     *
     * @param Blueprint $table
     * @return void
     */
    public function makeFilesColumns(Blueprint &$table): void
    {
        $table->string('file_name');
        $table->string('file_original_name');
        $table->unsignedBigInteger('file_size');
        $table->string('file_extension')->nullable();
    }
}
