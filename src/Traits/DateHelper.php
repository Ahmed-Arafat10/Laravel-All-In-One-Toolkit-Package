<?php

namespace AhmedArafat\AllInOne\Traits;

use DateTimeInterface;
use Illuminate\Support\Carbon;
use InvalidArgumentException;
use Throwable;

trait DateHelper
{
    /**
     * Parse a date value into a Carbon instance.
     *
     * Accepts strings, DateTime, or Carbon instances.
     * Optionally allows overriding the timezone.
     *
     * @param DateTimeInterface|string $date
     * @param string|null $timeZone
     * @return Carbon
     *
     * @throws InvalidArgumentException When the date cannot be parsed
     */
    protected function parseDate(DateTimeInterface|string $date, string $timeZone = null): Carbon
    {
        try {
            return Carbon::parse($date, $timeZone);
        } catch (Throwable $e) {
            throw new InvalidArgumentException('Invalid date provided.');
        }
    }

    /**
     * Get the exact difference between two dates in years, months, and days.
     *
     * Example output:
     * [
     *   'years'  => 2,
     *   'months' => 3,
     *   'days'   => 15
     * ]
     *
     * @param DateTimeInterface|string $startDate
     * @param DateTimeInterface|string $endDate
     * @return array{years:int, months:int, days:int}
     *
     * @throws InvalidArgumentException When start date is after end date
     */
    public function diffInYearsMonthsDays(DateTimeInterface|string $startDate, DateTimeInterface|string $endDate): array
    {
        $start = $this->parseDate($startDate);
        $end = $this->parseDate($endDate);
        if ($start->greaterThan($end)) {
            throw new InvalidArgumentException('Start date must be before end date.');
        }
        return [
            'years' => $start->diffInYears($end),
            'months' => $start->diffInMonths($end) % 12,
            'days' => $start->copy()
                ->addMonths($start->diffInMonths($end))
                ->diffInDays($end),
        ];
    }

    /**
     * Determine if a date falls between two other dates (inclusive).
     *
     * @param DateTimeInterface|string $date
     * @param DateTimeInterface|string $startDate
     * @param DateTimeInterface|string $endDate
     * @return bool
     */
    public function isBetweenDates(DateTimeInterface|string $date, DateTimeInterface|string $startDate, DateTimeInterface|string $endDate): bool
    {
        $date = $this->parseDate($date);
        $start = $this->parseDate($startDate);
        $end = $this->parseDate($endDate);

        return $date->betweenIncluded($start, $end);
    }

    /**
     * Check if a given date is in the past.
     *
     * @param DateTimeInterface|string $date
     * @return bool
     */
    public function isPastDate(DateTimeInterface|string $date): bool
    {
        return $this->parseDate($date)->isPast();
    }

    /**
     * Check if a given date is in the future.
     *
     * @param DateTimeInterface|string $date
     * @return bool
     */
    public function isFutureDate(DateTimeInterface|string $date): bool
    {
        return $this->parseDate($date)->isFuture();
    }

    /**
     * Convert a date to ISO 8601 format.
     *
     * Commonly used in APIs and external integrations.
     *
     * @param DateTimeInterface|string $date
     * @return string
     */
    public function toIsoDate(DateTimeInterface|string $date): string
    {
        return $this->parseDate($date)->toIso8601String();
    }

    /**
     * Format a date safely using a given format and optional timezone.
     *
     * Returns null if the date cannot be parsed or formatted.
     *
     * @param DateTimeInterface|string $date
     * @param string $format
     * @param string|null $timeZone
     * @return string|null
     */
    public function formatDate(DateTimeInterface|string $date, string $format = 'Y-m-d', string $timeZone = null): ?string
    {
        try {
            return $this->parseDate($date, $timeZone)->format($format);
        } catch (Throwable $e) {
            return null;
        }
    }

    /**
     * Calculate age in years from a given birthdate.
     *
     * @param DateTimeInterface|string $birthDate
     * @return int
     */
    public function calculateAge(DateTimeInterface|string $birthDate): int
    {
        return $this->parseDate($birthDate)->age;
    }

    /**
     * Add business days (weekdays only) to a given date.
     *
     * Weekends are skipped automatically.
     *
     * @param DateTimeInterface|string $date
     * @param int $days
     * @return Carbon
     */
    public function addBusinessDays(DateTimeInterface|string $date, int $days): Carbon
    {
        return $this->parseDate($date)->addWeekdays($days);
    }
}
