<?php

namespace AhmedArafat\AllInOne\Traits;

use Illuminate\Support\Str;
use UnitEnum;
use BackedEnum;

/**
 * Trait EnumHelper
 *
 * A set of helper methods to simplify working with PHP 8.1+ enums,
 * especially when integrating enums with Laravel APIs, validation,
 * database values, and UI layers.
 *
 * This trait supports:
 * - Unit enums (name-only)
 * - Backed enums (int|string values)
 *
 * IMPORTANT:
 * Methods that deal with values (`value`, `id`, database usage)
 * require the enum to be a BackedEnum.
 *
 */
trait EnumHelper
{
    /**
     * Get all enum values.
     *
     * Works ONLY for backed enums.
     *
     * Useful for:
     * - Validation rules (Rule::in)
     * - Database constraints
     * - API filtering
     *
     * @return array<int|string>
     */
    public static function values(): array
    {
        return array_map(
            fn(BackedEnum $case) => $case->value,
            self::cases()
        );
    }

    /**
     * Get all enum case names.
     *
     * Works for both UnitEnum and BackedEnum.
     *
     * @return array<string>
     */
    public static function names(): array
    {
        return array_map(
            fn(UnitEnum $case) => $case->name,
            self::cases()
        );
    }

    /**
     * Get an enum case by its name.
     *
     * Example:
     * Status::fromName('PENDING')
     *
     * @param string $name
     * @return UnitEnum|null  The enum case if found, otherwise null
     */
    public static function fromName(string $name): ?UnitEnum
    {
        foreach (self::cases() as $case) {
            if ($case->name === $name) {
                return $case;
            }
        }

        return null;
    }

    /**
     * Get enum value by enum name.
     *
     * Only applies to backed enums.
     *
     * Example:
     * Status::valueFromName('PENDING') // 1
     *
     * @param string $name
     * @return int|string|null
     */
    public static function valueFromName(string $name): int|string|null
    {
        $case = self::fromName($name);

        return $case instanceof BackedEnum ? $case->value : null;
    }

    /**
     * Get enum name by its backed value.
     *
     * Example:
     * Status::nameFromValue(1) // "PENDING"
     *
     * @param int|string $value
     * @return string|null
     */
    public static function nameFromValue(int|string $value): ?string
    {
        foreach (self::cases() as $case) {
            if ($case instanceof BackedEnum && $case->value === $value) {
                return $case->name;
            }
        }

        return null;
    }

    /**
     * Transform enum cases into an array suitable for APIs,
     * dropdowns, selects, or frontend consumption.
     *
     * Output format:
     * [
     *   ['id' => 1, 'name' => 'Pending'],
     *   ['id' => 2, 'name' => 'Approved'],
     * ]
     *
     * Only works for backed enums.
     *
     * @return array<int, array{id:int|string, name:string}>
     */
    public static function toModelArray(): array
    {
        return array_map(function (BackedEnum $case) {
            return [
                'id' => $case->value,
                'name' => self::humanize($case->name),
            ];
        }, self::cases());
    }

    /**
     * Get a human-readable name from a backed enum value.
     *
     * Example:
     * Status::humanNameFromValue(1) // "Pending"
     *
     * @param int|string $value
     * @return string|null
     */
    public static function humanNameFromValue(int|string $value): ?string
    {
        foreach (self::cases() as $case) {
            if ($case instanceof BackedEnum && $case->value === $value) {
                return self::humanize($case->name);
            }
        }

        return null;
    }

    /**
     * Convert an enum case name to a human-readable string.
     *
     * Example:
     * USER_PENDING_APPROVAL -> User Pending Approval
     *
     * @param string $name
     * @return string
     */
    protected static function humanize(string $name): string
    {
        return Str::of($name)
            ->lower()
            ->replace('_', ' ')
            ->title()
            ->toString();
    }
}
