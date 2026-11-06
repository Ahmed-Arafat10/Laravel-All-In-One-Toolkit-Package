<?php

namespace AhmedArafat\AllInOne\Helpers;

/**
 * Supply the default date and time format string.
 */
class DateHelper
{
    /**
     * Return the supplied format string or the package default.
     *
     * @param string $format
     * @return string
     */
    public static function format(string $format = 'Y-m-d h:i A'): string
    {
        return $format;
    }
}
