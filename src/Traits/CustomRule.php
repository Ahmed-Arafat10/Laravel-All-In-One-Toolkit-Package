<?php
declare(strict_types=1);

namespace AhmedArafat\AllInOne\Traits;

use Closure;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Exists;
use Illuminate\Validation\Rules\Password;
use Illuminate\Validation\Rules\Unique;

trait CustomRule
{
    public function strongPassword(
        int  $min = 8,
        bool $hasMixed = true,
        bool $hasNumbers = true,
        bool $hasSymbols = true,
        bool $uncompromised = true
    ): Password
    {
        $passwordRule = Password::min($min);

        $hasMixed && $passwordRule->mixedCase();
        $hasNumbers && $passwordRule->numbers();
        $hasSymbols && $passwordRule->symbols();
        $uncompromised && $passwordRule->uncompromised();

        return $passwordRule;
    }

    public function existsRule(
        string   $table,
        string   $column = 'id',
        ?Closure $whereClosure = null
    ): Exists
    {
        $existsRule = Rule::exists($table, $column);

        if ($whereClosure) {
            $existsRule->where($whereClosure);
        }

        return $existsRule;
    }

    public function uniqueRule(
        string   $table,
        string   $column,
        ?Closure $whereClosure = null,
        mixed    $ignoreId = null
    ): Unique
    {
        $uniqueRule = Rule::unique($table, $column);

        if ($whereClosure) {
            $uniqueRule->where($whereClosure);
        }

        if ($ignoreId !== null) {
            $uniqueRule->ignore($ignoreId);
        }

        return $uniqueRule;
    }
}