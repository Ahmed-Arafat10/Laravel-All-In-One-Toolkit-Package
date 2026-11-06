<?php

namespace AhmedArafat\AllInOne\Exceptions;

use AhmedArafat\AllInOne\Traits\ApiResponser;
use AhmedArafat\AllInOne\Traits\JsonApiResponser;
use Exception;

/**
 * Provide JSON response helpers to exception subclasses through JsonApiResponser.
 */
class ExceptionWithJsonApiResponser extends Exception
{
    use JsonApiResponser;
}
