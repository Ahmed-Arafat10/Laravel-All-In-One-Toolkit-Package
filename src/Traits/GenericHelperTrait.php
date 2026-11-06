<?php

namespace AhmedArafat\AllInOne\Traits;

use Exception;

/**
 * Format byte counts for display.
 */
trait GenericHelperTrait
{
    /**
     * Divide the byte count by 1,048,576 and format it with two decimal places and an MB suffix.
     *
     * @param int|float $bytes
     * @return string
     */
    public function convertBytes($bytes): string
    {
        $megabytes = $bytes / 1048576;
        return number_format($megabytes, 2) . ' MB';
    }
}
