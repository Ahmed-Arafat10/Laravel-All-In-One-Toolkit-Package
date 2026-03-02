<?php
declare(strict_types=1);

namespace AhmedArafat\AllInOne\Traits;

use Closure;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Exists;
use Illuminate\Validation\Rules\Password;
use Illuminate\Validation\Rules\Unique;

/**
 * Trait CustomRule
 *
 * A collection of reusable Laravel validation rule builders.
 * Intended to be used inside FormRequest classes or validators
 * to keep validation logic expressive and consistent.
 */
trait CustomRule
{
    /**
     * Build a strong password validation rule.
     *
     * This method wraps Laravel's Password rule and allows
     * enabling or disabling individual strength constraints.
     *
     * @param int $min Minimum password length
     * @param bool $hasMixed Require upper & lower case letters
     * @param bool $hasNumbers Require numeric characters
     * @param bool $hasSymbols Require special characters
     * @param bool $uncompromised Check password against known data leaks
     *
     * @return Password
     */
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

    /**
     * Build an "exists" validation rule with optional constraints.
     *
     * Useful for validating foreign keys or relational references,
     * with support for additional query conditions.
     *
     * @param string $table Database table name
     * @param string $column Column to check existence against
     * @param Closure|null $whereClosure Optional query constraints
     *
     * @return Exists
     */
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

    /**
     * Build a "unique" validation rule with optional constraints.
     *
     * Commonly used for fields like email, username, or phone number,
     * with support for update scenarios using ignore().
     *
     * @param string $table Database table name
     * @param string $column Column that must be unique
     * @param Closure|null $whereClosure Optional query constraints
     * @param mixed|null $ignoreId ID to ignore during uniqueness check
     *
     * @return Unique
     */
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

    /**
     * Build an "exists" rule that validates only active records.
     *
     * Assumes a boolean-like column (e.g. is_active) is used
     * to indicate active rows.
     *
     * @param string $table Database table name
     * @param string $column Column to check existence against
     * @param string $activeColumn Column that represents active status
     *
     * @return Exists
     */
    public function existsActiveRule(
        string $table,
        string $column = 'id',
        string $activeColumn = 'is_active'
    ): Exists
    {
        return Rule::exists($table, $column)
            ->where($activeColumn, true);
    }

    /**
     * Build a scoped "unique" validation rule.
     *
     * Ensures uniqueness within a specific scope, such as:
     * - unique email per company
     * - unique username per tenant
     *
     * @param string $table Database table name
     * @param string $column Column that must be unique
     * @param string $scopeColumn Column defining the scope
     * @param mixed $scopeValue Value of the scope column
     * @param mixed|null $ignoreId ID to ignore during uniqueness check
     *
     * @return Unique
     */
    public function uniqueScopedRule(
        string $table,
        string $column,
        string $scopeColumn,
        mixed  $scopeValue,
        mixed  $ignoreId = null
    ): Unique
    {
        $rule = Rule::unique($table, $column)
            ->where($scopeColumn, $scopeValue);

        if ($ignoreId !== null) {
            $rule->ignore($ignoreId);
        }

        return $rule;
    }

    public function EnglishTextOnly(): string
    {
        return 'regex:/^[A-Za-z -]+$/u';
    }

    public function ArabicTextOnly(): string
    {
        return 'regex:/^[\p{Arabic} ]+$/u';
    }
}
