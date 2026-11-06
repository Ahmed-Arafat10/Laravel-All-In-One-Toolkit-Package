<?php

namespace AhmedArafat\AllInOne\Traits;

use Exception;

trait GenericHelperTrait
{
    /**
     * @param $bytes
     * @return string
     */
    public function convertBytes($bytes): string
    {
        $megabytes = $bytes / 1048576;
        return number_format($megabytes, 2) . ' MB';
    }
}
